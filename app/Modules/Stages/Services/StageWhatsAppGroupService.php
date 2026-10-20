<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Services\WhatsAppBridgeClient;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Groupe WhatsApp des stagiaires — un seul par année.
 *
 *  - créé automatiquement au premier stagiaire de l'année (ou à la main),
 *    le numéro du CAP le crée et les responsables de division en sont
 *    administrateurs ; on peut aussi utiliser un groupe existant (lien) ;
 *  - chaque stagiaire y est ajouté à son début de stage ; si WhatsApp
 *    refuse l'ajout (réglages de confidentialité), le lien du groupe lui
 *    est envoyé en privé ;
 *  - il en est retiré une semaine après la fin de son stage ;
 *  - le groupe est dissous sur demande (tous les membres retirés, puis le
 *    numéro du CAP le quitte).
 */
class StageWhatsAppGroupService
{
    private const REMOVE_AFTER_DAYS = 7;

    public function __construct(
        private readonly WhatsAppBridgeClient $phones,
        private readonly Notifier $notifier,
    ) {}

    public function activeGroup(int $year): ?object
    {
        return DB::table('stage_whatsapp_groups')->where('year', $year)->whereNull('dissolved_at')->orderByDesc('id')->first();
    }

    public function overview(): array
    {
        $year  = (int) BusinessHours::today()->format('Y');
        $group = $this->activeGroup($year);
        $today = BusinessHours::today()->toDateString();

        $members = $group ? StageRequest::where('wa_group_id', $group->id)->whereNotNull('wa_added_at')->whereNull('wa_removed_at')->count() : 0;
        $waiting = StageRequest::where('status', StageStatus::ACCEPTED)
            ->where('date_debut', '<=', $today)->where('date_fin', '>=', $today)
            ->whereNull('wa_added_at')->count();

        return [
            'year'    => $year,
            'group'   => $group ? [
                'id' => $group->id, 'name' => $group->name, 'origin' => $group->origin,
                'created_at' => $group->created_at, 'members' => $members,
            ] : null,
            'waiting' => $waiting,
            'history' => DB::table('stage_whatsapp_groups')->orderByDesc('id')->limit(10)
                ->get(['id', 'year', 'name', 'origin', 'created_at', 'dissolved_at']),
        ];
    }

    /** Crée le groupe de l'année (responsables de division administrateurs). */
    public function create(int $year, ?User $by): object
    {
        if ($this->activeGroup($year)) {
            throw new BusinessException("Le groupe {$year} existe déjà.", 'STAGE_WA_GROUP_EXISTS', 409);
        }
        $admins = $this->managerPhones();
        if (!$admins) {
            throw new BusinessException('Aucun responsable de division (formation continue) avec un numéro : impossible de créer le groupe.', 'STAGE_WA_NO_ADMIN', 422);
        }

        $name = "Stagiaires CAP-EPAC {$year}";
        $res  = $this->call('/groups/create', ['subject' => $name, 'participants' => $admins, 'admins' => $admins]);

        $id = DB::table('stage_whatsapp_groups')->insertGetId([
            'year' => $year, 'jid' => $res['jid'], 'name' => $name, 'origin' => 'created',
            'created_by' => $by?->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('stage_whatsapp_groups')->find($id);
    }

    /** Utilise un groupe existant à partir de son lien d'invitation. */
    public function link(int $year, string $inviteLink, User $by): object
    {
        if (!preg_match('#^(?:https?://)?chat\.whatsapp\.com/(?:invite/)?([A-Za-z0-9]{10,40})/?$#', trim($inviteLink), $m)) {
            throw new BusinessException("Lien invalide. Il ressemble à https://chat.whatsapp.com/AbCdEf…", 'STAGE_WA_BAD_LINK', 422);
        }
        if ($this->activeGroup($year)) {
            throw new BusinessException("Un groupe est déjà utilisé pour {$year}. Dissolvez-le d'abord.", 'STAGE_WA_GROUP_EXISTS', 409);
        }

        $jid  = $this->call('/groups/join', ['code' => $m[1]])['jid'];
        $info = $this->call('/groups/info', ['jid' => $jid]);

        if (DB::table('stage_whatsapp_groups')->where('jid', $jid)->exists()) {
            DB::table('stage_whatsapp_groups')->where('jid', $jid)->update([
                'year' => $year, 'dissolved_at' => null, 'dissolved_by' => null, 'updated_at' => now(),
            ]);
        } else {
            DB::table('stage_whatsapp_groups')->insert([
                'year' => $year, 'jid' => $jid, 'name' => mb_substr((string) ($info['subject'] ?? "Stagiaires {$year}"), 0, 100),
                'origin' => 'linked', 'created_by' => $by->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $group = DB::table('stage_whatsapp_groups')->where('jid', $jid)->first();
        $group->is_admin = (bool) ($info['is_admin'] ?? false);

        return $group;
    }

    /** Retire tous les membres puis quitte le groupe. */
    public function dissolve(int $groupId, User $by): void
    {
        $group = DB::table('stage_whatsapp_groups')->where('id', $groupId)->whereNull('dissolved_at')->first();
        if (!$group) {
            throw new BusinessException('Groupe introuvable ou déjà dissous.', 'STAGE_WA_NOT_FOUND', 404);
        }

        $info = $this->call('/groups/info', ['jid' => $group->jid]);
        $me   = $this->myNumber();
        $others = array_values(array_filter($info['participants'] ?? [], fn ($p) => $p !== $me && preg_match('/^\d{8,20}$/', $p)));

        foreach (array_chunk($others, 50) as $chunk) {
            $this->call('/groups/participants', ['jid' => $group->jid, 'action' => 'remove', 'participants' => $chunk]);
        }
        $this->call('/groups/leave', ['jid' => $group->jid]);

        DB::table('stage_whatsapp_groups')->where('id', $groupId)->update([
            'dissolved_at' => now(), 'dissolved_by' => $by->id, 'updated_at' => now(),
        ]);
        StageRequest::where('wa_group_id', $groupId)->whereNull('wa_removed_at')->whereNotNull('wa_added_at')
            ->update(['wa_removed_at' => now()]);
    }

    /**
     * Tâche quotidienne : ajoute les stagiaires qui commencent, retire ceux
     * qui ont fini depuis une semaine.
     *
     * @return array{added: int, invited: int, removed: int, errors: int}
     */
    public function sync(): array
    {
        $stats = ['added' => 0, 'invited' => 0, 'removed' => 0, 'errors' => 0];
        $today = BusinessHours::today();

        if (!$this->bridgeConnected()) {
            Log::info('[Stages] Groupe WhatsApp : bridge non connecté, synchronisation reportée');
            return $stats;
        }

        // ── Ajouts ──────────────────────────────────────────────────────
        $toAdd = StageRequest::where('status', StageStatus::ACCEPTED)
            ->where('date_debut', '<=', $today->toDateString())
            ->where('date_fin', '>=', $today->toDateString())
            ->whereNull('wa_added_at')->whereNull('wa_invite_sent_at')
            ->orderBy('id')->limit(100)->get();

        foreach ($toAdd->groupBy(fn ($r) => (int) $r->date_debut->format('Y')) as $year => $reqs) {
            try {
                $group = $this->activeGroup((int) $year) ?? $this->create((int) $year, null);
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('[Stages] Groupe WhatsApp indisponible', ['year' => $year, 'error' => $e->getMessage()]);
                continue;
            }

            foreach ($reqs as $req) {
                try {
                    $phone = $this->digits($req->whatsapp);
                    if (!$phone) {
                        continue;
                    }
                    $res    = $this->call('/groups/participants', ['jid' => $group->jid, 'action' => 'add', 'participants' => [$phone]]);
                    $status = (string) ($res['results'][$phone] ?? 'unknown');

                    if (in_array($status, ['200', '409'], true)) {
                        $req->forceFill(['wa_group_id' => $group->id, 'wa_added_at' => now(), 'wa_removed_at' => null])->save();
                        $this->log($req, 'whatsapp_group_added', $group->name);
                        $stats['added']++;
                    } elseif ($status === 'not_on_whatsapp') {
                        $req->forceFill(['wa_group_id' => $group->id, 'wa_invite_sent_at' => now()])->save();
                        $this->log($req, 'whatsapp_group_unreachable', 'Numéro absent de WhatsApp');
                    } else {
                        // Refus (confidentialité) : lien envoyé en privé.
                        $link = $this->call('/groups/invite-code', ['jid' => $group->jid])['link'] ?? null;
                        if ($link) {
                            $this->notifier->whatsapp($req->whatsapp, WhatsAppText::compose('Groupe des stagiaires', [
                                'Bonjour ' . trim($req->civilite . ' ' . $req->fullName()) . ',',
                                "Rejoignez le groupe WhatsApp des stagiaires du CAP-EPAC : les informations utiles à votre stage y sont partagées.\n{$link}",
                            ]), "stage-groupe:{$req->reference}", 'Stages');
                        }
                        $req->forceFill(['wa_group_id' => $group->id, 'wa_invite_sent_at' => now()])->save();
                        $this->log($req, 'whatsapp_group_invited', $group->name);
                        $stats['invited']++;
                    }
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    Log::warning('[Stages] Ajout au groupe WhatsApp échoué', ['reference' => $req->reference, 'error' => $e->getMessage()]);
                }
            }
        }

        // ── Retraits (une semaine après la fin) ─────────────────────────
        $limit    = $today->subDays(self::REMOVE_AFTER_DAYS)->toDateString();
        $toRemove = StageRequest::whereNotNull('wa_group_id')->whereNotNull('wa_added_at')->whereNull('wa_removed_at')
            ->where('date_fin', '<', $limit)->orderBy('id')->limit(100)->get();

        foreach ($toRemove as $req) {
            try {
                $group = DB::table('stage_whatsapp_groups')->where('id', $req->wa_group_id)->first();
                if ($group && !$group->dissolved_at && ($phone = $this->digits($req->whatsapp))) {
                    $this->call('/groups/participants', ['jid' => $group->jid, 'action' => 'remove', 'participants' => [$phone]]);
                }
                $req->forceFill(['wa_removed_at' => now()])->save();
                $this->log($req, 'whatsapp_group_removed', $group?->name);
                $stats['removed']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::warning('[Stages] Retrait du groupe WhatsApp échoué', ['reference' => $req->reference, 'error' => $e->getMessage()]);
            }
        }

        return $stats;
    }

    // ── Interne ──────────────────────────────────────────────────────────

    /** Appel au service WhatsApp ; lève une BusinessException lisible en cas d'échec. */
    protected function call(string $endpoint, array $payload): array
    {
        $url = rtrim((string) config('services.whatsapp_bridge.url', 'http://127.0.0.1:3005'), '/') . $endpoint;
        $key = (string) config('services.whatsapp_bridge.api_key', '');

        try {
            $req = Http::timeout(60)->acceptJson();
            if ($key !== '') {
                $req = $req->withHeaders(['X-Api-Key' => $key]);
            }
            $res = $req->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error('[Stages] Service WhatsApp injoignable', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new BusinessException('Le service WhatsApp est injoignable. Réessayez plus tard.', 'STAGE_WA_UNREACHABLE', 503);
        }

        if (!$res->successful() || !$res->json('success')) {
            $msg = match ($res->status()) {
                503     => "WhatsApp n'est pas connecté sur le serveur. Connectez-le depuis CAP WhatsApp.",
                403     => "Le numéro du CAP n'est pas administrateur de ce groupe.",
                404     => 'Groupe introuvable ou lien expiré.',
                422     => 'Aucun participant joignable sur WhatsApp.',
                default => "L'opération WhatsApp a échoué. Réessayez plus tard.",
            };
            Log::warning('[Stages] Opération groupe WhatsApp refusée', ['endpoint' => $endpoint, 'status' => $res->status(), 'body' => $res->json('error')]);
            throw new BusinessException($msg, 'STAGE_WA_FAILED', $res->status() >= 400 && $res->status() < 600 ? $res->status() : 502);
        }

        return (array) $res->json();
    }

    protected function bridgeConnected(): bool
    {
        try {
            return $this->phones->isConnected();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function myNumber(): ?string
    {
        try {
            $status = $this->phones->getStatus();
            $id = $status['user']['id'] ?? null;
            return $id ? explode(':', explode('@', $id)[0])[0] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return string[] */
    private function managerPhones(): array
    {
        return StageAccess::managers()->pluck('phone')->map(fn ($p) => $this->digits((string) $p))->filter()->unique()->values()->all();
    }

    /** Numéro au format attendu par le service : chiffres seuls, indicatif inclus. */
    private function digits(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        $n = $this->phones->normalizePhone($raw);

        return $n ? ltrim($n, '+') : null;
    }

    private function log(StageRequest $req, string $action, ?string $comment): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => $action,
            'from_status' => $req->status, 'to_status' => $req->status,
            'actor' => 'systeme', 'comment' => $comment,
        ]);
    }
}

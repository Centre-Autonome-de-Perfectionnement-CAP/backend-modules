<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Services\WhatsAppBridgeClient;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stagiaires arrivés avant le système (ou en dehors) : le responsable de
 * division les invite par leur seul numéro WhatsApp ; chacun remplit lui-même
 * son dossier en ouvrant son lien personnel (7 jours, usage unique).
 *
 *  - stage en cours : parcours normal à partir de l'enregistrement (pointage,
 *    rapports) ; les jours d'avant ne comptent pas comme absences ;
 *  - stage terminé : seul le rapport final est dû, puis l'attestation.
 *
 * Les documents portent les dates déclarées (note de service datée
 * du début du stage, attestation de sa fin) ; le système garde la date réelle
 * d'enregistrement (historique, recorded_at, attendance_from).
 */
class InternInvitationService
{
    public const TTL_DAYS = 7;
    private const MAX_PER_BATCH = 30;
    /** Heure portée sur la note de service déclarée. */
    private const DECLARED_NOTE_TIME = '08:30';

    public function __construct(
        private readonly Notifier $notifier,
        private readonly WhatsAppBridgeClient $bridge,
        private readonly StageRequestService $requests,
        private readonly StageSettingsService $settings,
        private readonly StageNotifier $stageNotifier,
    ) {}

    /**
     * @param string[] $rawPhones
     * @return array{invited: string[], already_intern: string[], invalid: string[]}
     */
    public function invite(array $rawPhones, User $by): array
    {
        $phones = [];
        $invalid = [];
        foreach ($rawPhones as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') continue;
            $n = $this->normalize($raw);
            if ($n) $phones[$n] = true;
            else $invalid[] = $raw;
        }
        $phones = array_keys($phones);

        if (!$phones) {
            throw new BusinessException(
                $invalid ? 'Aucun numéro valide. Exemple attendu : 01 97 00 00 00.' : 'Indiquez au moins un numéro WhatsApp.',
                'STAGE_INVITE_NO_PHONE', 422,
            );
        }
        if (count($phones) > self::MAX_PER_BATCH) {
            throw new BusinessException('30 numéros au maximum par envoi.', 'STAGE_INVITE_TOO_MANY', 422);
        }

        $result = ['invited' => [], 'already_intern' => [], 'invalid' => $invalid];
        foreach ($phones as $phone) {
            if ($this->hasActiveStage($phone)) {
                $result['already_intern'][] = $phone;
                continue;
            }
            $token = Str::random(48);
            DB::transaction(function () use ($phone, $by, $token) {
                // Un numéro ne garde qu'une invitation valable : l'ancienne est annulée.
                DB::table('stage_intern_invitations')->where('phone', $phone)->whereNull('used_at')->whereNull('revoked_at')
                    ->update(['revoked_at' => now(), 'updated_at' => now()]);
                DB::table('stage_intern_invitations')->insert([
                    'phone' => $phone, 'token_hash' => hash('sha256', $token), 'invited_by' => $by->id,
                    'expires_at' => now()->addDays(self::TTL_DAYS), 'last_sent_at' => now(), 'send_count' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
            $this->sendLink($phone, $token);
            $result['invited'][] = $phone;
        }

        return $result;
    }

    public function resend(int $id): void
    {
        $inv = $this->pending($id);
        $token = Str::random(48);
        DB::table('stage_intern_invitations')->where('id', $inv->id)->update([
            'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(self::TTL_DAYS),
            'last_sent_at' => now(), 'send_count' => $inv->send_count + 1, 'updated_at' => now(),
        ]);
        $this->sendLink($inv->phone, $token);
    }

    public function revoke(int $id): void
    {
        $inv = $this->pending($id, allowRevoked: true);
        DB::table('stage_intern_invitations')->where('id', $inv->id)->update(['revoked_at' => now(), 'updated_at' => now()]);
    }

    public function list(): array
    {
        return DB::table('stage_intern_invitations as i')
            ->leftJoin('users as u', 'u.id', '=', 'i.invited_by')
            ->leftJoin('stage_requests as r', 'r.id', '=', 'i.stage_request_id')
            ->orderByDesc('i.id')->limit(200)
            ->get([
                'i.id', 'i.phone', 'i.expires_at', 'i.last_sent_at', 'i.send_count', 'i.used_at', 'i.revoked_at', 'i.created_at',
                'i.stage_request_id', 'r.reference',
                DB::raw("TRIM(CONCAT(COALESCE(r.civilite,''),' ',COALESCE(r.nom,''),' ',COALESCE(r.prenoms,''))) as intern_name"),
                DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as invited_by_name"),
            ])
            ->map(function ($i) {
                $i->status = $i->used_at ? 'used' : ($i->revoked_at ? 'revoked' : (now()->greaterThan($i->expires_at) ? 'expired' : 'pending'));
                return $i;
            })->all();
    }

    // ── Côté stagiaire (public) ──────────────────────────────────────────

    public function findValid(string $token): ?object
    {
        $inv = DB::table('stage_intern_invitations')->where('token_hash', hash('sha256', $token))->first();

        return ($inv && !$inv->used_at && !$inv->revoked_at && now()->lessThan($inv->expires_at)) ? $inv : null;
    }

    public function maskPhone(string $phone): string
    {
        $d = preg_replace('/\D/', '', $phone);

        return strlen($d) >= 4 ? '••••••' . substr($d, -4) : $phone;
    }

    /**
     * Le stagiaire complète son dossier : stage enregistré (accepté), espace
     * stagiaire ouvert, note de service aux dates déclarées.
     *
     * @return array{account: StageInternAccount, request: StageRequest}
     */
    public function register(string $token, array $data, ?UploadedFile $notePapier): array
    {
        $today = BusinessHours::today();
        $start = CarbonImmutable::parse($data['date_debut'], BusinessHours::TZ)->startOfDay();
        $end   = CarbonImmutable::parse($data['date_fin'], BusinessHours::TZ)->startOfDay();
        if ($start->greaterThan($today)) {
            throw new BusinessException('Ce lien concerne un stage déjà commencé. Pour un stage à venir, faites une demande de stage sur le site.', 'STAGE_INVITE_FUTURE', 422);
        }
        if ($end->lessThan($start)) {
            throw new BusinessException('La date de fin doit suivre la date de début.', 'STAGE_DATES', 422);
        }
        if ($start->lessThan($today->subYears(3))) {
            throw new BusinessException('La date de début est trop ancienne. Contactez le responsable de division.', 'STAGE_DATES', 422);
        }

        $stored = null;
        try {
            $result = DB::transaction(function () use ($token, $data, $notePapier, $start, $end, $today, &$stored) {
                $inv = DB::table('stage_intern_invitations')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
                if (!$inv || $inv->used_at || $inv->revoked_at || now()->greaterThanOrEqualTo($inv->expires_at)) {
                    throw new BusinessException("Ce lien n'est plus valable. Demandez-en un nouveau au responsable de division.", 'STAGE_INVITE_INVALID', 410);
                }
                if ($this->hasActiveStage($inv->phone)) {
                    throw new BusinessException('Un stage est déjà enregistré pour ce numéro. Connectez-vous à votre espace stagiaire.', 'STAGE_INVITE_DUPLICATE', 409);
                }

                $reference = $this->reference();
                $files = [];
                if ($notePapier) {
                    $ext = strtolower($notePapier->getClientOriginalExtension() ?: 'pdf');
                    $stored = $notePapier->storeAs("stages/{$reference}", "note-papier.{$ext}", StageRequestService::DISK);
                    $files['note_papier'] = ['path' => $stored, 'name' => 'Note de service (papier)', 'size' => (int) $notePapier->getSize()];
                }

                $req = StageRequest::create([
                    'reference' => $reference, 'status' => StageStatus::ACCEPTED, 'origin' => 'invitation',
                    'civilite' => $data['civilite'], 'nom' => mb_strtoupper(trim($data['nom'])), 'prenoms' => trim($data['prenoms']),
                    'date_naissance' => $data['date_naissance'], 'lieu_naissance' => trim($data['lieu_naissance']),
                    'nationalite' => trim($data['nationalite']), 'etablissement_origine' => trim($data['etablissement_origine']),
                    'filiere' => trim($data['filiere']), 'cycle' => $data['cycle'], 'annee_etude' => $data['annee_etude'] ?? null, 'theme_souhaite' => $data['theme'] ?? null,
                    'duree_souhaitee' => $this->requests->durationLabel($start->toDateString(), $end->toDateString()),
                    'email' => mb_strtolower(trim($data['email'])), 'whatsapp' => $inv->phone,
                    'files' => $files, 'consent_at' => now(),
                    'service' => ($data['service'] ?? null) ?: $this->settings->get('default_service'),
                    'lieu' => $this->settings->get('default_lieu'),
                    'horaires' => $this->settings->get('default_horaires'),
                    'date_debut' => $start->toDateString(), 'date_fin' => $end->toDateString(),
                    'date_debut_reelle' => $start->toDateString(),
                    'attendance_from' => $today->toDateString(),
                    'accepted_at' => now(), 'activated_at' => now(),
                ]);

                $account = StageInternAccount::create(['stage_request_id' => $req->id, 'password' => $data['password']]);

                DB::table('stage_intern_invitations')->where('id', $inv->id)
                    ->update(['used_at' => now(), 'stage_request_id' => $req->id, 'updated_at' => now()]);

                StageHistory::create([
                    'stage_request_id' => $req->id, 'action' => 'registered_by_invitation',
                    'from_status' => null, 'to_status' => StageStatus::ACCEPTED, 'actor' => 'demandeur',
                    'comment' => 'Stage arrivé avant le système, enregistré par le stagiaire le '
                        . $today->locale('fr')->isoFormat('D MMMM YYYY') . ' (dates déclarées : du '
                        . $this->requests->frDate($start->toDateString()) . ' au ' . $this->requests->frDate($end->toDateString()) . ')',
                ]);

                return ['account' => $account, 'request' => $req];
            });
        } catch (\Throwable $e) {
            if ($stored) {
                Storage::disk(StageRequestService::DISK)->delete($stored);
            }
            throw $e;
        }

        $req = $result['request']->fresh();

        // Note de service datée du début du stage déclaré.
        try {
            $this->requests->issueDeclaredNote($req, CarbonImmutable::parse($start->toDateString() . ' ' . self::DECLARED_NOTE_TIME, BusinessHours::TZ));
        } catch (\Throwable $e) {
            Log::error('[Stages] Note de service déclarée non émise', ['reference' => $req->reference, 'error' => $e->getMessage()]);
        }

        // Stage déjà terminé : l'attestation est préparée tout de suite (elle
        // attend le rapport final pour être validée).
        if ($end->lessThan($today)) {
            try {
                app(AttestationService::class)->prepare($req->fresh());
            } catch (\Throwable $e) {
                Log::warning('[Stages] Attestation non préparée', ['reference' => $req->reference, 'error' => $e->getMessage()]);
            }
        }

        $this->stageNotifier->toManagers($req, 'Stagiaire enregistré', [
            'Un stagiaire invité a complété son dossier : son stage est enregistré dans le système.',
            $end->lessThan($today) ? 'Stage déjà terminé : il doit déposer son rapport final pour obtenir son attestation.'
                : 'Stage en cours : il pointe et dépose ses rapports à partir d\'aujourd\'hui.',
        ], [
            'Stagiaire' => $req->fullName(), 'Établissement' => $req->etablissement_origine,
            'Période déclarée' => 'du ' . $this->requests->frDate($req->date_debut) . ' au ' . $this->requests->frDate($req->date_fin),
        ], 'stage-invitation-enregistre');

        return ['account' => $result['account'], 'request' => $req];
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function pending(int $id, bool $allowRevoked = false): object
    {
        $inv = DB::table('stage_intern_invitations')->where('id', $id)->first();
        if (!$inv) {
            throw new BusinessException('Invitation introuvable.', 'STAGE_INVITE_NOT_FOUND', 404);
        }
        if ($inv->used_at) {
            throw new BusinessException('Ce stagiaire a déjà complété son dossier.', 'STAGE_INVITE_USED', 409);
        }
        if ($inv->revoked_at && !$allowRevoked) {
            throw new BusinessException('Cette invitation a été annulée.', 'STAGE_INVITE_REVOKED', 409);
        }

        return $inv;
    }

    private function sendLink(string $phone, string $token): void
    {
        $url = rtrim((string) config('app.frontend_url'), '/') . '/stage/rejoindre?token=' . $token;

        $this->notifier->whatsapp(
            $phone,
            WhatsAppText::compose('Enregistrement de votre stage', [
                'Bonjour,',
                'Le CAP-EPAC vous invite à enregistrer votre stage dans son système de gestion des stages.',
                "Complétez votre dossier en ouvrant ce lien personnel (valable " . self::TTL_DAYS . " jours, utilisable une seule fois) :\n{$url}",
                '_Si vous n\'attendiez pas ce message, ignorez-le._',
            ]),
            'stage-invitation-stagiaire',
            'Stages',
        );
    }

    /** Un stage en cours (ou en attente de décision) existe déjà pour ce numéro ? */
    private function hasActiveStage(string $phone): bool
    {
        $digits = substr(preg_replace('/\D/', '', $phone), -8);

        return StageRequest::whereIn('status', array_merge(StageStatus::ACTIVE, [StageStatus::ACCEPTED]))
            ->where('whatsapp', 'like', '%' . $digits)
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', BusinessHours::today()->toDateString()))
            ->exists();
    }

    private function reference(): string
    {
        return $this->requests->newReference();
    }

    private function normalize(string $raw): ?string
    {
        $n = $this->bridge->normalizePhone($raw);

        return $n ? '+' . ltrim($n, '+') : null;
    }
}

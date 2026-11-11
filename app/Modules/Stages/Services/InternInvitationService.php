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
 *
 * Invitation en binôme : deux numéros, un lien chacun. Le premier inscrit
 * déclare les dates ; le second reprend ses dates, son service et ses
 * superviseurs, et le binôme se forme à son inscription (rapport final
 * individuel ou commun, choisi à l'invitation). Si le second ne s'inscrit
 * pas, le premier poursuit seul et le responsable est prévenu.
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
        private readonly BinomeService $binomes,
    ) {}

    /**
     * @param string[] $rawPhones
     * @param bool $pair deux numéros invités ensemble, en binôme
     * @return array{invited: string[], already_intern: string[], invalid: string[]}
     */
    public function invite(array $rawPhones, User $by, bool $pair = false, ?string $finalMode = null): array
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
        if ($pair) {
            return $this->invitePair($phones, $invalid, $by, $finalMode);
        }

        $result = ['invited' => [], 'already_intern' => [], 'invalid' => $invalid];
        foreach ($phones as $phone) {
            if ($this->hasActiveStage($phone)) {
                $result['already_intern'][] = $phone;
                continue;
            }
            $token = \App\Modules\Core\Support\LinkToken::make();
            DB::transaction(fn () => $this->store($phone, $token, $by));
            $this->sendLink($phone, $token);
            $result['invited'][] = $phone;
        }

        return $result;
    }

    /**
     * Deux numéros invités en binôme : un lien chacun, reliés.
     *
     * @param string[] $phones numéros valides
     * @param string[] $invalid
     */
    private function invitePair(array $phones, array $invalid, User $by, ?string $finalMode): array
    {
        if (!$this->binomes->finalReady()) {
            throw new BusinessException('L\'invitation en binôme sera disponible dans quelques minutes.', 'STAGE_INVITE_PAIR_NOT_READY', 503);
        }
        if ($invalid || count($phones) !== 2) {
            throw new BusinessException($invalid
                ? 'Numéro invalide : ' . implode(', ', $invalid) . '. Exemple attendu : 01 97 00 00 00.'
                : 'Indiquez les deux numéros du binôme (deux numéros différents).', 'STAGE_INVITE_PAIR', 422);
        }
        foreach ($phones as $phone) {
            if ($this->hasActiveStage($phone)) {
                throw new BusinessException("Le {$phone} a déjà un stage en cours : il ne peut pas être invité en binôme.", 'STAGE_INVITE_DUPLICATE', 409);
            }
        }
        $mode = isset(BinomeService::FINAL_MODES[(string) $finalMode]) ? $finalMode : BinomeService::FINAL_INDIVIDUEL;
        $tokens = [\App\Modules\Core\Support\LinkToken::make(), \App\Modules\Core\Support\LinkToken::make()];
        DB::transaction(function () use ($phones, $tokens, $by, $mode) {
            $a = $this->store($phones[0], $tokens[0], $by, ['final_mode' => $mode]);
            $b = $this->store($phones[1], $tokens[1], $by, ['final_mode' => $mode, 'pair_id' => $a]);
            DB::table('stage_intern_invitations')->where('id', $a)->update(['pair_id' => $b]);
        });
        foreach ($phones as $i => $phone) {
            $this->sendLink($phone, $tokens[$i], true);
        }

        return ['invited' => $phones, 'already_intern' => [], 'invalid' => []];
    }

    /** Enregistre une invitation ; un numéro ne garde qu'une invitation valable (l'ancienne est annulée). */
    private function store(string $phone, string $token, User $by, array $extra = []): int
    {
        DB::table('stage_intern_invitations')->where('phone', $phone)->whereNull('used_at')->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);

        return DB::table('stage_intern_invitations')->insertGetId([
            'phone' => $phone, 'token_hash' => hash('sha256', $token), 'invited_by' => $by->id,
            'expires_at' => now()->addDays(self::TTL_DAYS), 'last_sent_at' => now(), 'send_count' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ] + $extra);
    }

    public function resend(int $id): void
    {
        $inv = $this->pending($id);
        $token = \App\Modules\Core\Support\LinkToken::make();
        DB::table('stage_intern_invitations')->where('id', $inv->id)->update([
            'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(self::TTL_DAYS),
            'last_sent_at' => now(), 'send_count' => $inv->send_count + 1, 'updated_at' => now(),
        ]);
        $this->sendLink($inv->phone, $token, !empty($inv->pair_id));
    }

    public function revoke(int $id): void
    {
        $inv = $this->pending($id, allowRevoked: true);
        DB::table('stage_intern_invitations')->where('id', $inv->id)->update(['revoked_at' => now(), 'updated_at' => now()]);
    }

    public function list(): array
    {
        $pairs = $this->binomes->finalReady();

        return DB::table('stage_intern_invitations as i')
            ->leftJoin('users as u', 'u.id', '=', 'i.invited_by')
            ->leftJoin('stage_requests as r', 'r.id', '=', 'i.stage_request_id')
            ->orderByDesc('i.id')->limit(200)
            ->get([
                'i.id', 'i.phone', 'i.expires_at', 'i.last_sent_at', 'i.send_count', 'i.used_at', 'i.revoked_at', 'i.created_at',
                'i.stage_request_id', 'r.reference', ...($pairs ? ['i.pair_id', 'i.final_mode'] : []),
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

    /**
     * Invitation en binôme : le binôme déjà inscrit (ses dates, son service
     * s'imposent), ou null (invitation seule, ou binôme pas encore inscrit).
     */
    public function registeredPartner(object $inv): ?StageRequest
    {
        $partnerInv = $this->partnerInvitation($inv);
        if (!$partnerInv || !$partnerInv->used_at || !$partnerInv->stage_request_id) {
            return null;
        }
        $req = StageRequest::find($partnerInv->stage_request_id);

        return $req && $req->status === StageStatus::ACCEPTED && !$req->team_id ? $req : null;
    }

    /** Invitation en binôme : y a-t-il un binôme invité (inscrit ou non) ? */
    public function pairInfo(object $inv): ?array
    {
        $partnerInv = $this->partnerInvitation($inv);
        if (!$partnerInv) {
            return null;
        }
        $partner = $this->registeredPartner($inv);

        return [
            'final_mode' => $inv->final_mode ?? BinomeService::FINAL_INDIVIDUEL,
            'partner'    => $partner ? [
                'name' => $partner->fullName(), 'date_debut' => $partner->date_debut?->toDateString(),
                'date_fin' => $partner->date_fin?->toDateString(), 'service' => $partner->service,
            ] : null,
        ];
    }

    /** Espace stagiaire : inscrit en binôme, en attente de l'inscription de l'autre. */
    public function awaitingPartner(StageRequest $req): ?array
    {
        if ($req->team_id || !$this->binomes->finalReady()) {
            return null;
        }
        $inv = DB::table('stage_intern_invitations')->where('stage_request_id', $req->id)->whereNotNull('pair_id')->first();
        $partnerInv = $inv ? $this->partnerInvitation($inv) : null;
        if (!$partnerInv || $partnerInv->used_at || $partnerInv->revoked_at || now()->greaterThanOrEqualTo($partnerInv->expires_at)) {
            return null;
        }

        return ['expires_at' => CarbonImmutable::parse($partnerInv->expires_at)->toIso8601String(),
            'final_mode' => $inv->final_mode ?? BinomeService::FINAL_INDIVIDUEL];
    }

    /**
     * Tâche quotidienne : binômes invités dont l'un ne s'est pas inscrit
     * (lien expiré ou annulé) ; l'inscrit poursuit seul, lui et le
     * responsable sont prévenus une fois.
     */
    public function closeLonelyPairs(): int
    {
        if (!$this->binomes->finalReady()) {
            return 0;
        }
        $done = 0;
        $rows = DB::table('stage_intern_invitations')->whereNotNull('pair_id')->whereNotNull('used_at')->whereNull('pair_notice_at')->limit(100)->get();
        foreach ($rows as $inv) {
            $partnerInv = $this->partnerInvitation($inv);
            if ($partnerInv && ($partnerInv->used_at || (!$partnerInv->revoked_at && now()->lessThan($partnerInv->expires_at)))) {
                continue; // inscrit, ou lien encore valable
            }
            DB::table('stage_intern_invitations')->where('id', $inv->id)->update(['pair_notice_at' => now()]);
            $req = StageRequest::find($inv->stage_request_id);
            if (!$req || $req->team_id) {
                continue;
            }
            $phone = $partnerInv ? $this->maskPhone($partnerInv->phone) : 'son binôme';
            try {
                $this->stageNotifier->toManagers($req, 'Binôme non formé', [
                    "Le binôme invité avec {$req->fullName()} ({$phone}) ne s'est pas inscrit : son lien n'est plus valable. {$req->fullName()} poursuit seul(e).",
                    'Pour les réunir, renvoyez le lien depuis les invitations, puis formez le binôme depuis la page Binômes.',
                ], ['Stagiaire' => $req->fullName()], 'stage-invitation-binome');
                $this->stageNotifier->toDemandeur($req, 'Votre binôme ne s\'est pas inscrit', [
                    'Votre binôme n\'a pas enregistré son stage à temps : vous poursuivez seul(e) pour le moment. Le responsable de division en est informé.',
                ], ctaLabel: 'Mon espace stagiaire', ctaUrl: $this->stageNotifier->spaceUrl('/stage'), context: 'stage-binome');
            } catch (\Throwable $e) {
                Log::error('[Stages] Binôme non formé : message impossible', ['reference' => $req->reference, 'error' => $e->getMessage()]);
            }
            $done++;
        }

        return $done;
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
        // Binôme déjà inscrit : ses dates et son service s'imposent.
        $invRow  = DB::table('stage_intern_invitations')->where('token_hash', hash('sha256', $token))->first();
        $partner = $invRow ? $this->registeredPartner($invRow) : null;
        if ($partner) {
            $data['date_debut'] = $partner->date_debut->toDateString();
            $data['date_fin']   = $partner->date_fin->toDateString();
            $data['service']    = $partner->service;
        }
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

        if ($partner) {
            $this->joinPartner($partner, $req, $invRow);
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

    private function partnerInvitation(object $inv): ?object
    {
        if (empty($inv->pair_id) || !$this->binomes->finalReady()) {
            return null;
        }

        return DB::table('stage_intern_invitations')->where('id', $inv->pair_id)->first();
    }

    /**
     * Le second du binôme s'inscrit : il reprend les superviseurs du premier,
     * puis le binôme se forme (au nom de la personne qui a invité).
     */
    private function joinPartner(StageRequest $first, StageRequest $second, object $inv): void
    {
        try {
            $by = User::find($inv->invited_by) ?? User::find(\App\Modules\Stages\Support\StageAccess::managers()->first()?->id);
            if (!$by) {
                throw new \RuntimeException('Aucun responsable pour former le binôme');
            }
            DB::table('stage_supervisions')->where('stage_request_id', $first->id)->get(['user_id'])
                ->each(fn ($s) => DB::table('stage_supervisions')->insertOrIgnore([
                    'stage_request_id' => $second->id, 'user_id' => $s->user_id, 'assigned_by' => $by->id, 'created_at' => now(),
                ]));
            $this->binomes->form($first->fresh(), $second->fresh(), $by, null, $inv->final_mode ?? BinomeService::FINAL_INDIVIDUEL);
        } catch (\Throwable $e) {
            Log::warning('[Stages] Binôme invité non formé', ['first' => $first->reference, 'second' => $second->reference, 'error' => $e->getMessage()]);
            $this->stageNotifier->toManagers($second, 'Binôme non formé', [
                "{$second->fullName()} et {$first->fullName()} étaient invités en binôme, mais le binôme n'a pas pu être formé automatiquement.",
                'Formez-le depuis la page Binômes.',
            ], ['Stagiaires' => "{$first->fullName()} et {$second->fullName()}"], 'stage-invitation-binome');
        }
    }

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

    private function sendLink(string $phone, string $token, bool $pair = false): void
    {
        $url = rtrim((string) config('app.frontend_url'), '/') . '/stage/rejoindre/' . $token;

        $this->notifier->whatsapp(
            $phone,
            WhatsAppText::compose('Enregistrement de votre stage', array_values(array_filter([
                'Bonjour,',
                'Le CAP-EPAC vous invite à enregistrer votre stage dans son système de gestion des stages.',
                $pair ? 'Vous êtes invité(e) en binôme : votre binôme reçoit son propre lien. Chacun remplit son dossier ; vous serez réunis automatiquement.' : null,
                "Complétez votre dossier en ouvrant ce lien personnel (valable " . self::TTL_DAYS . " jours, utilisable une seule fois) :\n{$url}",
                '_Si vous n\'attendiez pas ce message, ignorez-le._',
            ]))),
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

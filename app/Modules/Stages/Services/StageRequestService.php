<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Core\Services\WhatsAppBridgeClient;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageNote;
use App\Modules\Stages\Models\StageProposal;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Circuit d'une demande de stage (Lot 1) :
 *
 *   dépôt ──► étude par le responsable de division
 *               ├─ proposition (dates, service, horaires) ──► demandeur
 *               │     ├─ accepte ──────────────► note de service (automatique)
 *               │     ├─ contre-propose ──► responsable : accepte la contre-proposition
 *               │     │                     ou fait une nouvelle proposition
 *               │     └─ sans réponse : relance à J+3, expiration à J+7
 *               ├─ proposition identique à la demande ──► note de service directe
 *               └─ rejet (motif par défaut proposé)
 *   après la note : toute modification ──► note rectificative numérotée
 */
class StageRequestService
{
    public const DISK = 'local';

    public const FILE_KEYS = [
        'cv'                => 'CV',
        'lettre_motivation' => 'Lettre de motivation ou de recommandation',
        'acte_naissance'    => 'Acte de naissance sécurisé',
    ];

    private const REF_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const DUREE_MOIS = ['1_mois' => 1, '2_mois' => 2, '3_mois' => 3, '4_a_6_mois' => 4, 'plus_6_mois' => 6];

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly StageSettingsService $settings,
    ) {}

    // ════════════════════════════════════════════════════════════════════
    // DÉPÔT (public)
    // ════════════════════════════════════════════════════════════════════

    /** @param array<string, UploadedFile> $files */
    public function submit(array $data, array $files): StageRequest
    {
        if (!$this->settings->get('requests_open')) {
            throw new BusinessException((string) $this->settings->get('closed_message'), 'STAGE_REQUESTS_CLOSED', 403);
        }

        $whatsapp = $this->normalizePhone($data['telephone']);
        $email    = mb_strtolower(trim($data['email']));

        $active = StageRequest::query()
            ->whereIn('status', StageStatus::ACTIVE)
            ->where(fn ($q) => $q->where('email', $email)->orWhere('whatsapp', $whatsapp))
            ->exists();
        if ($active) {
            throw new BusinessException(
                "Une demande de stage est déjà en cours avec cet email ou ce numéro. Suivez-la avec la référence que vous avez reçue.",
                'STAGE_REQUEST_ALREADY_ACTIVE',
                409,
            );
        }

        $reference = $this->newReference();
        $stored    = [];

        try {
            foreach (self::FILE_KEYS as $key => $_label) {
                $file = $files[$key];
                $ext  = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
                $stored[$key] = $file->storeAs("stages/{$reference}/demande", "{$key}.{$ext}", self::DISK);
                if (!$stored[$key]) {
                    throw new \RuntimeException("Enregistrement impossible : {$key}");
                }
            }

            $req = DB::transaction(function () use ($data, $reference, $stored, $whatsapp, $email) {
                $req = StageRequest::create([
                    'reference'             => $reference,
                    'status'                => StageStatus::SUBMITTED,
                    'civilite'              => $data['civilite'],
                    'nom'                   => $data['nom'],
                    'prenoms'               => $data['prenoms'],
                    'date_naissance'        => $data['date_naissance'],
                    'lieu_naissance'        => $data['lieu_naissance'],
                    'nationalite'           => $data['nationalite'],
                    'etablissement_origine' => $data['etablissement_origine'],
                    'filiere_niveau'        => $data['filiere_niveau'],
                    'date_debut_souhaitee'  => $data['date_debut_souhaitee'] ?? null,
                    'theme_souhaite'        => $data['theme_souhaite'] ?? null,
                    'duree_souhaitee'       => $data['duree_stage'],
                    'email'                 => $email,
                    'whatsapp'              => $whatsapp,
                    'files'                 => $stored,
                    'consent_at'            => now(),
                ]);
                $this->log($req, 'submitted', null, StageStatus::SUBMITTED, 'demandeur');

                return $req;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $path) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $e;
        }

        $this->notifier->toDemandeur(
            $req,
            'Demande de stage reçue',
            [
                'Nous avons bien reçu votre demande de stage. Elle sera étudiée par le responsable de division.',
                'Vous serez informé(e) ici et par email de la suite. Conservez votre référence : elle vous permet de suivre votre demande à tout moment.',
            ],
            ['Référence' => $req->reference, 'Durée souhaitée' => $this->dureeLabel($req->duree_souhaitee)],
            ctaLabel: 'Suivre ma demande',
            ctaUrl: $this->notifier->suiviUrl($req),
            context: 'stage-recue',
        );

        $this->notifier->toManagers($req, 'Nouvelle demande de stage', [
            'Une nouvelle demande de stage vient d\'être déposée.',
        ], [
            'Référence'     => $req->reference,
            'Demandeur'     => $req->fullName(),
            'Établissement' => $req->etablissement_origine,
            'Formation'     => $req->filiere_niveau,
            'Début souhaité' => $req->date_debut_souhaitee ? $this->frDate($req->date_debut_souhaitee) : 'Non précisé',
            'Durée'         => $this->dureeLabel($req->duree_souhaitee),
        ], 'stage-nouvelle');

        return $req;
    }

    // ════════════════════════════════════════════════════════════════════
    // DÉCISIONS DU RESPONSABLE DE DIVISION
    // ════════════════════════════════════════════════════════════════════

    /**
     * Proposition pré-remplie : la demande elle-même, complétée par les
     * réglages par défaut. Le responsable n'a souvent qu'à l'envoyer.
     */
    public function suggestedTerms(StageRequest $req): array
    {
        $last = $req->proposals()->reorder('id', 'desc')->first();
        if ($last && $last->author === 'staff' && $last->kind === 'proposal') {
            // Nouvelle proposition : on repart de la précédente.
            return $this->proposalTerms($last);
        }
        if ($last && $last->kind === 'counter') {
            $base = $req->proposals()->where('author', 'staff')->reorder('id', 'desc')->first();
            return [
                'service'    => $base->service ?? $this->settings->get('default_service'),
                'lieu'       => $base->lieu ?? $this->settings->get('default_lieu'),
                'date_debut' => $last->date_debut?->toDateString(),
                'date_fin'   => $last->date_fin?->toDateString(),
                'horaires'   => $base->horaires ?? $this->settings->get('default_horaires'),
            ];
        }

        $today = BusinessHours::today();
        $start = $req->date_debut_souhaitee && CarbonImmutable::parse($req->date_debut_souhaitee)->greaterThanOrEqualTo($today)
            ? CarbonImmutable::parse($req->date_debut_souhaitee)
            : $today->addWeek()->next(CarbonImmutable::MONDAY);
        $months = self::DUREE_MOIS[$req->duree_souhaitee] ?? 3;

        return [
            'service'    => $this->settings->get('default_service'),
            'lieu'       => $this->settings->get('default_lieu'),
            'date_debut' => $start->toDateString(),
            'date_fin'   => $start->addMonthsNoOverflow($months)->subDay()->toDateString(),
            'horaires'   => $this->settings->get('default_horaires'),
        ];
    }

    public function propose(StageRequest $req, array $terms, ?string $message, User $by): StageRequest
    {
        $this->assertStatus($req, StageStatus::OPEN_FOR_DECISION);
        $this->assertDates($terms);

        $direct = $this->matchesRequest($req, $terms);

        DB::transaction(function () use ($req, $terms, $message, $by, $direct) {
            $from = $req->status;
            StageProposal::create($this->proposalRow($req, 'staff', $by->id, 'proposal', $terms, $message));

            if ($direct) {
                $this->applyTerms($req, $terms);
                $req->fill(['status' => StageStatus::ACCEPTED, 'accepted_at' => now(), 'decided_by' => $by->id])->save();
                $this->log($req, 'accepted_direct', $from, StageStatus::ACCEPTED, 'staff', $by->id, $message,
                    ['raison' => 'Conditions identiques à la demande']);
                $this->issueNote($req, 'initiale', null, $by->id);
                return;
            }

            $req->fill([
                'status'               => StageStatus::PROPOSAL_SENT,
                'proposal_sent_at'     => now(),
                'proposal_reminded_at' => null,
                'decided_by'           => $by->id,
            ])->save();
            $this->log($req, 'proposal_sent', $from, StageStatus::PROPOSAL_SENT, 'staff', $by->id, $message, $this->termsMeta($terms));
        });

        $req->refresh();

        if ($direct) {
            $this->notifyNote($req, 'initiale');
            return $req;
        }

        $expiry = (int) $this->settings->get('proposal_expiry_days');
        $this->notifier->toDemandeur(
            $req,
            'Proposition pour votre stage',
            [
                'Votre demande de stage a retenu notre attention. Voici les conditions que nous vous proposons.',
                "Merci d'indiquer si elles vous conviennent ou de proposer d'autres dates. Sans réponse de votre part sous {$expiry} jours, la proposition expirera et votre demande sera close.",
            ],
            $this->termsFields($terms),
            highlight: $message ?: null,
            highlightTitle: 'Message du responsable',
            ctaLabel: 'Répondre à la proposition',
            ctaUrl: $this->notifier->suiviUrl($req),
            context: 'stage-proposition',
        );

        return $req;
    }

    public function acceptCounter(StageRequest $req, User $by): StageRequest
    {
        $this->assertStatus($req, [StageStatus::COUNTER_PROPOSED]);
        $terms = $this->suggestedTerms($req);
        $this->assertDates($terms);

        DB::transaction(function () use ($req, $terms, $by) {
            $this->applyTerms($req, $terms);
            $req->fill(['status' => StageStatus::ACCEPTED, 'accepted_at' => now(), 'decided_by' => $by->id])->save();
            $this->log($req, 'accepted_counter', StageStatus::COUNTER_PROPOSED, StageStatus::ACCEPTED, 'staff', $by->id, null, $this->termsMeta($terms));
            $this->issueNote($req, 'initiale', null, $by->id);
        });

        $req->refresh();
        $this->notifyNote($req, 'initiale');

        return $req;
    }

    public function reject(StageRequest $req, string $motif, User $by): StageRequest
    {
        $this->assertStatus($req, StageStatus::OPEN_FOR_DECISION);

        DB::transaction(function () use ($req, $motif, $by) {
            $from = $req->status;
            $req->fill([
                'status' => StageStatus::REJECTED, 'rejected_at' => now(),
                'rejection_reason' => $motif, 'decided_by' => $by->id,
            ])->save();
            $this->log($req, 'rejected', $from, StageStatus::REJECTED, 'staff', $by->id, $motif);
        });

        $this->notifier->toDemandeur(
            $req,
            'Réponse à votre demande de stage',
            ["Après étude, nous ne pouvons pas donner une suite favorable à votre demande de stage."],
            ['Référence' => $req->reference],
            highlight: $motif,
            highlightTitle: 'Motif',
            context: 'stage-rejet',
        );

        return $req->refresh();
    }

    /** Modification après la note de service → note rectificative. */
    public function rectify(StageRequest $req, array $terms, string $motif, User $by): StageRequest
    {
        $this->assertStatus($req, [StageStatus::ACCEPTED]);
        $this->assertDates($terms, allowPastStart: true);

        $before = $this->currentTerms($req);
        if ($this->sameTerms($before, $terms)) {
            throw new BusinessException('Aucune modification à enregistrer.', 'STAGE_NOTHING_CHANGED', 422);
        }

        DB::transaction(function () use ($req, $terms, $motif, $by, $before) {
            $this->applyTerms($req, $terms);
            $req->save();
            $this->log($req, 'rectified', StageStatus::ACCEPTED, StageStatus::ACCEPTED, 'staff', $by->id, $motif,
                ['avant' => $this->termsMeta($before), 'apres' => $this->termsMeta($terms)]);
            $this->issueNote($req, 'rectificative', $motif, $by->id);
        });

        $req->refresh();
        $this->notifyNote($req, 'rectificative', $motif);

        return $req;
    }

    // ════════════════════════════════════════════════════════════════════
    // RÉPONSE DU DEMANDEUR (public, après code)
    // ════════════════════════════════════════════════════════════════════

    public function respondAccept(StageRequest $req): StageRequest
    {
        $this->assertStatus($req, [StageStatus::PROPOSAL_SENT]);
        $proposal = $this->lastStaffProposal($req);
        $terms    = $this->proposalTerms($proposal);

        DB::transaction(function () use ($req, $terms) {
            StageProposal::create($this->proposalRow($req, 'demandeur', null, 'accept', $terms, null));
            $this->applyTerms($req, $terms);
            $req->fill(['status' => StageStatus::ACCEPTED, 'accepted_at' => now()])->save();
            $this->log($req, 'accepted', StageStatus::PROPOSAL_SENT, StageStatus::ACCEPTED, 'demandeur', null, null, $this->termsMeta($terms));
            $this->issueNote($req, 'initiale', null, null);
        });

        $req->refresh();
        $this->notifyNote($req, 'initiale');
        $this->notifier->toManagers($req, 'Proposition de stage acceptée', [
            'Le demandeur a accepté votre proposition. La note de service a été émise et lui a été envoyée automatiquement.',
        ], ['Référence' => $req->reference, 'Stagiaire' => $req->fullName()] + $this->termsFields($terms), 'stage-accord');

        return $req;
    }

    public function respondCounter(StageRequest $req, string $dateDebut, string $dateFin, ?string $message): StageRequest
    {
        $this->assertStatus($req, [StageStatus::PROPOSAL_SENT]);
        $base  = $this->proposalTerms($this->lastStaffProposal($req));
        $terms = array_merge($base, ['date_debut' => $dateDebut, 'date_fin' => $dateFin]);
        $this->assertDates($terms);

        if ($terms['date_debut'] === $base['date_debut'] && $terms['date_fin'] === $base['date_fin']) {
            throw new BusinessException('Ces dates sont celles déjà proposées. Choisissez « Accepter » si elles vous conviennent.', 'STAGE_SAME_DATES', 422);
        }

        DB::transaction(function () use ($req, $terms, $message) {
            StageProposal::create($this->proposalRow($req, 'demandeur', null, 'counter', $terms, $message));
            $req->fill(['status' => StageStatus::COUNTER_PROPOSED])->save();
            $this->log($req, 'counter_proposed', StageStatus::PROPOSAL_SENT, StageStatus::COUNTER_PROPOSED, 'demandeur', null, $message, $this->termsMeta($terms));
        });

        $req->refresh();
        $this->notifier->toDemandeur(
            $req,
            'Votre proposition a été transmise',
            ['Vos dates ont bien été transmises au responsable de division. Vous recevrez sa réponse ici et par email.'],
            ['Référence' => $req->reference, 'Début proposé' => $this->frDate($terms['date_debut']), 'Fin proposée' => $this->frDate($terms['date_fin'])],
            ctaLabel: 'Suivre ma demande',
            ctaUrl: $this->notifier->suiviUrl($req),
            context: 'stage-contre-proposition',
        );
        $this->notifier->toManagers($req, 'Contre-proposition de dates', [
            'Le demandeur propose d\'autres dates pour son stage.',
        ], [
            'Référence'     => $req->reference,
            'Demandeur'     => $req->fullName(),
            'Début proposé' => $this->frDate($terms['date_debut']),
            'Fin proposée'  => $this->frDate($terms['date_fin']),
            'Message'       => $message,
        ], 'stage-contre-proposition');

        return $req;
    }

    // ════════════════════════════════════════════════════════════════════
    // ÉCHÉANCES (tâche planifiée quotidienne)
    // ════════════════════════════════════════════════════════════════════

    /** @return array{reminded: int, expired: int} */
    public function processDeadlines(): array
    {
        $reminderDays = (int) $this->settings->get('proposal_reminder_days');
        $expiryDays   = (int) $this->settings->get('proposal_expiry_days');
        $stats        = ['reminded' => 0, 'expired' => 0];

        $pending = StageRequest::query()
            ->where('status', StageStatus::PROPOSAL_SENT)
            ->whereNotNull('proposal_sent_at')
            ->where('proposal_sent_at', '<=', now()->subDays($reminderDays))
            ->orderBy('id')
            ->limit(500)
            ->get();

        foreach ($pending as $req) {
            try {
                if ($req->proposal_sent_at->lessThanOrEqualTo(now()->subDays($expiryDays))) {
                    $this->expire($req);
                    $stats['expired']++;
                } elseif ($req->proposal_reminded_at === null) {
                    $this->remind($req, $expiryDays);
                    $stats['reminded']++;
                }
            } catch (\Throwable $e) {
                Log::error('[Stages] Échéance non traitée', ['reference' => $req->reference, 'error' => $e->getMessage()]);
            }
        }

        return $stats;
    }

    private function remind(StageRequest $req, int $expiryDays): void
    {
        $updated = StageRequest::whereKey($req->id)
            ->where('status', StageStatus::PROPOSAL_SENT)
            ->whereNull('proposal_reminded_at')
            ->update(['proposal_reminded_at' => now()]);
        if (!$updated) {
            return;
        }
        $this->log($req, 'reminder_sent', StageStatus::PROPOSAL_SENT, StageStatus::PROPOSAL_SENT, 'systeme');

        $deadline = $req->proposal_sent_at->copy()->addDays($expiryDays);
        $this->notifier->toDemandeur(
            $req,
            'Rappel : une proposition vous attend',
            [
                "Nous attendons toujours votre réponse à notre proposition de stage.",
                'Sans réponse de votre part avant le ' . $this->frDate($deadline) . ', la proposition expirera et votre demande sera close.',
            ],
            $this->termsFields($this->proposalTerms($this->lastStaffProposal($req))),
            ctaLabel: 'Répondre à la proposition',
            ctaUrl: $this->notifier->suiviUrl($req),
            context: 'stage-relance',
        );
    }

    private function expire(StageRequest $req): void
    {
        $updated = StageRequest::whereKey($req->id)
            ->where('status', StageStatus::PROPOSAL_SENT)
            ->update(['status' => StageStatus::EXPIRED, 'expired_at' => now(), 'updated_at' => now()]);
        if (!$updated) {
            return;
        }
        $req->refresh();
        $this->log($req, 'expired', StageStatus::PROPOSAL_SENT, StageStatus::EXPIRED, 'systeme', null, 'Proposition restée sans réponse');

        $this->notifier->toDemandeur(
            $req,
            'Proposition expirée',
            [
                "Faute de réponse, notre proposition de stage a expiré et votre demande a été close.",
                'Vous pouvez déposer une nouvelle demande à tout moment.',
            ],
            ['Référence' => $req->reference],
            context: 'stage-expiree',
        );
        $this->notifier->toManagers($req, 'Proposition expirée', [
            'Le demandeur n\'a pas répondu à la proposition dans le délai : la demande a été close automatiquement.',
        ], ['Référence' => $req->reference, 'Demandeur' => $req->fullName()], 'stage-expiree');
    }

    // ════════════════════════════════════════════════════════════════════
    // NOTE DE SERVICE
    // ════════════════════════════════════════════════════════════════════

    /**
     * Enregistre la note (contenu figé). Le PDF sera produit à partir de ce
     * contenu lorsque le modèle sera intégré.
     */
    private function issueNote(StageRequest $req, string $kind, ?string $motif, ?int $by): StageNote
    {
        $year     = (int) BusinessHours::today()->format('Y');
        $previous = StageNote::where('stage_request_id', $req->id)->orderByDesc('version')->lockForUpdate()->first();

        if ($kind === 'initiale' || !$previous) {
            $prefix = "NS-STG-{$year}-";
            $last   = StageNote::where('kind', 'initiale')->where('number', 'like', $prefix . '%')
                ->lockForUpdate()->orderByDesc('number')->value('number');
            $seq    = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
            $number = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $version = 1;
        } else {
            $base    = StageNote::where('stage_request_id', $req->id)->where('kind', 'initiale')->value('number');
            $version = $previous->version + 1;
            $number  = $base . '-R' . ($version - 1);
            $kind    = 'rectificative';
        }

        return StageNote::create([
            'stage_request_id' => $req->id,
            'number'           => $number,
            'kind'             => $kind,
            'version'          => $version,
            'snapshot'         => [
                'reference'      => $req->reference,
                'civilite'       => $req->civilite,
                'nom'            => $req->nom,
                'prenoms'        => $req->prenoms,
                'etablissement'  => $req->etablissement_origine,
                'formation'      => $req->filiere_niveau,
                'theme'          => $req->theme_souhaite,
            ] + $this->currentTerms($req),
            'motif'     => $motif,
            'issued_by' => $by,
            'issued_at' => now(),
        ]);
    }

    private function notifyNote(StageRequest $req, string $kind, ?string $motif = null): void
    {
        $note  = $req->notes()->reorder('version', 'desc')->first();
        $terms = $this->currentTerms($req);

        if ($kind === 'rectificative') {
            $this->notifier->toDemandeur(
                $req,
                'Note de service rectificative',
                ['Les conditions de votre stage ont été modifiées. Voici les nouvelles conditions, qui remplacent les précédentes.'],
                ['Note n°' => $note?->number] + $this->termsFields($terms),
                highlight: $motif,
                highlightTitle: 'Motif de la modification',
                ctaLabel: 'Consulter mon dossier',
                ctaUrl: $this->notifier->suiviUrl($req),
                context: 'stage-note-rectificative',
            );
            return;
        }

        $this->notifier->toDemandeur(
            $req,
            'Stage accepté — note de service',
            [
                'Félicitations, votre stage au CAP-EPAC est confirmé. Voici les conditions retenues dans votre note de service.',
                'Le jour de votre arrivée, vous recevrez un lien pour confirmer votre présence et activer votre espace stagiaire.',
            ],
            ['Note n°' => $note?->number] + $this->termsFields($terms),
            ctaLabel: 'Consulter mon dossier',
            ctaUrl: $this->notifier->suiviUrl($req),
            context: 'stage-note',
        );
    }

    // ════════════════════════════════════════════════════════════════════
    // OUTILS
    // ════════════════════════════════════════════════════════════════════

    public function currentTerms(StageRequest $req): array
    {
        return [
            'service'    => $req->service,
            'lieu'       => $req->lieu,
            'date_debut' => $req->date_debut?->toDateString(),
            'date_fin'   => $req->date_fin?->toDateString(),
            'horaires'   => $req->horaires,
        ];
    }

    public function termsFields(array $t): array
    {
        $h = $t['horaires'] ?? [];

        return [
            'Service'  => $t['service'] ?? null,
            'Lieu'     => $t['lieu'] ?? null,
            'Début'    => !empty($t['date_debut']) ? $this->frDate($t['date_debut']) : null,
            'Fin'      => !empty($t['date_fin']) ? $this->frDate($t['date_fin']) : null,
            'Durée'    => (!empty($t['date_debut']) && !empty($t['date_fin'])) ? $this->durationLabel($t['date_debut'], $t['date_fin']) : null,
            'Horaires' => $h ? $this->horairesLabel($h) : null,
        ];
    }

    public function horairesLabel(array $h): string
    {
        $fmt = fn ($v) => str_replace(':', 'h', (string) $v);

        return "{$fmt($h['arrivee'] ?? '')} - {$fmt($h['pause_debut'] ?? '')} et {$fmt($h['pause_fin'] ?? '')} - {$fmt($h['sortie'] ?? '')}";
    }

    public function durationLabel(string $start, string $end): string
    {
        $s    = CarbonImmutable::parse($start);
        $e    = CarbonImmutable::parse($end)->addDay();
        $diff = $s->diff($e);
        $parts = [];
        if ($diff->y) $parts[] = $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        if ($diff->m) $parts[] = $diff->m . ' mois';
        if ($diff->d) $parts[] = $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');

        return implode(' ', $parts) ?: '1 jour';
    }

    public function dureeLabel(?string $value): string
    {
        return [
            '1_mois' => '1 mois', '2_mois' => '2 mois', '3_mois' => '3 mois',
            '4_a_6_mois' => '4 à 6 mois', 'plus_6_mois' => 'Plus de 6 mois',
        ][$value] ?? (string) $value;
    }

    public function frDate(mixed $date): string
    {
        return CarbonImmutable::parse($date)->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    public function filePath(StageRequest $req, string $key): ?string
    {
        $path = $req->files[$key] ?? null;

        return $path && Storage::disk(self::DISK)->exists($path) ? $path : null;
    }

    private function assertStatus(StageRequest $req, array $allowed): void
    {
        if (!in_array($req->status, $allowed, true)) {
            throw new BusinessException(
                'Cette action n\'est plus possible : la demande est « ' . StageStatus::label($req->status) . ' ».',
                'STAGE_INVALID_STATUS',
                409,
            );
        }
    }

    private function assertDates(array $t, bool $allowPastStart = false): void
    {
        $start = CarbonImmutable::parse($t['date_debut']);
        $end   = CarbonImmutable::parse($t['date_fin']);

        if (!$allowPastStart && $start->lessThan(BusinessHours::today())) {
            throw new BusinessException('La date de début ne peut pas être passée.', 'STAGE_START_IN_PAST', 422);
        }
        if ($end->lessThanOrEqualTo($start)) {
            throw new BusinessException('La date de fin doit être après la date de début.', 'STAGE_END_BEFORE_START', 422);
        }
        if ($start->diffInDays($end) > 400) {
            throw new BusinessException('La durée du stage ne peut pas dépasser un an.', 'STAGE_TOO_LONG', 422);
        }
    }

    /** Dates proposées = dates demandées → pas besoin d'aller-retour. */
    private function matchesRequest(StageRequest $req, array $t): bool
    {
        $months = ['1_mois' => 1, '2_mois' => 2, '3_mois' => 3][$req->duree_souhaitee] ?? null;
        if (!$req->date_debut_souhaitee || !$months) {
            return false;
        }
        $start = CarbonImmutable::parse($req->date_debut_souhaitee);

        return $start->toDateString() === $t['date_debut']
            && $start->addMonthsNoOverflow($months)->subDay()->toDateString() === $t['date_fin'];
    }

    private function sameTerms(array $a, array $b): bool
    {
        $norm = fn (array $t) => [
            trim((string) ($t['service'] ?? '')), trim((string) ($t['lieu'] ?? '')),
            $t['date_debut'] ?? null, $t['date_fin'] ?? null, $t['horaires'] ?? null,
        ];

        return $norm($a) == $norm($b);
    }

    private function applyTerms(StageRequest $req, array $t): void
    {
        $req->fill([
            'service'    => $t['service'],
            'lieu'       => $t['lieu'],
            'date_debut' => $t['date_debut'],
            'date_fin'   => $t['date_fin'],
            'horaires'   => $t['horaires'],
        ]);
    }

    private function lastStaffProposal(StageRequest $req): StageProposal
    {
        $p = $req->proposals()->where('author', 'staff')->reorder('id', 'desc')->first();
        if (!$p) {
            throw new BusinessException('Aucune proposition en cours.', 'STAGE_NO_PROPOSAL', 409);
        }

        return $p;
    }

    private function proposalTerms(StageProposal $p): array
    {
        return [
            'service'    => $p->service,
            'lieu'       => $p->lieu,
            'date_debut' => $p->date_debut?->toDateString(),
            'date_fin'   => $p->date_fin?->toDateString(),
            'horaires'   => $p->horaires,
        ];
    }

    private function proposalRow(StageRequest $req, string $author, ?int $userId, string $kind, array $t, ?string $message): array
    {
        return [
            'stage_request_id' => $req->id,
            'author'           => $author,
            'user_id'          => $userId,
            'kind'             => $kind,
            'service'          => $t['service'] ?? null,
            'lieu'             => $t['lieu'] ?? null,
            'date_debut'       => $t['date_debut'] ?? null,
            'date_fin'         => $t['date_fin'] ?? null,
            'horaires'         => $t['horaires'] ?? null,
            'message'          => $message,
        ];
    }

    private function termsMeta(array $t): array
    {
        return array_intersect_key($t, array_flip(['service', 'lieu', 'date_debut', 'date_fin', 'horaires']));
    }

    private function log(
        StageRequest $req, string $action, ?string $from, ?string $to, string $actor,
        ?int $userId = null, ?string $comment = null, ?array $meta = null,
    ): void {
        StageHistory::create([
            'stage_request_id' => $req->id,
            'action'           => $action,
            'from_status'      => $from,
            'to_status'        => $to,
            'actor'            => $actor,
            'user_id'          => $userId,
            'comment'          => $comment,
            'meta'             => $meta,
        ]);
    }

    private function newReference(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = '';
            for ($j = 0; $j < 6; $j++) {
                $code .= self::REF_ALPHABET[random_int(0, strlen(self::REF_ALPHABET) - 1)];
            }
            $ref = 'STG-' . $code;
            if (!StageRequest::where('reference', $ref)->exists()) {
                return $ref;
            }
        }

        throw new \RuntimeException('Impossible de générer une référence unique.');
    }

    private function normalizePhone(string $raw): string
    {
        try {
            $n = app(WhatsAppBridgeClient::class)->normalizePhone($raw);
            if ($n) {
                return str_starts_with($n, '+') ? $n : '+' . ltrim($n, '+');
            }
        } catch (\Throwable) {
            // numéro conservé tel quel
        }

        return trim($raw);
    }
}

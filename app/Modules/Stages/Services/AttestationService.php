<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageAttestation;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Attestations de stage.
 *
 *  - J-7 avant la fin (vendredi si J-7 tombe un week-end) : attestation
 *    pré-remplie, le responsable est prévenu, le stagiaire aussi (conditions) ;
 *  - conditions : assiduité ≥ seuil (80 % par défaut) et rapport final validé ;
 *  - seul le responsable valide ; le secrétariat imprime (numéro proposé,
 *    modifiable), puis marque la remise (datée) ; scan facultatif ;
 *  - attestation exceptionnelle : le responsable, avec son mot de passe et
 *    un motif, passe outre les conditions ;
 *  - après son stage, le stagiaire peut en demander une (motif) ;
 *  - relances : responsable / secrétariat, puis chaque lundi.
 */
class AttestationService
{
    public const KIND_LABELS = [
        'normale'        => 'Attestation de fin de stage',
        'exceptionnelle' => 'Attestation exceptionnelle',
        'demande'        => 'Demandée par le stagiaire',
    ];

    public const STATUS_LABELS = [
        'a_valider'  => 'À valider (responsable)',
        'a_imprimer' => 'À imprimer (secrétariat)',
        'imprimee'   => 'Imprimée - à remettre',
        'remise'     => 'Remise au stagiaire',
        'annulee'    => 'Annulée',
    ];

    public const PUBLIC_LABELS = [
        'a_valider'  => 'En préparation',
        'a_imprimer' => 'Validée - en cours d\'impression',
        'imprimee'   => 'Prête : à retirer au secrétariat',
        'remise'     => 'Remise',
        'annulee'    => 'Annulée',
    ];

    public const MOTIF_TYPES = [
        'non_recue' => 'Attestation non reçue',
        'perte'     => 'Perte de l\'attestation',
        'duplicata' => 'Besoin d\'un duplicata',
        'autre'     => 'Autre motif',
    ];

    /** Statuts « en cours » : une seule attestation en cours par stagiaire. */
    public const OPEN = ['a_valider', 'a_imprimer', 'imprimee'];

    public const DAYS_BEFORE_END = 7;

    /** Corrections possibles avant validation (longueur maximale). */
    public const EDITABLE = ['pays_naissance' => 60, 'type_stage' => 40, 'structure' => 200];

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly StageSettingsService $settings,
        private readonly StageRequestService $requests,
        private readonly AttendanceService $attendance,
        private readonly ReportService $reports,
    ) {}

    // ════════════════════════════════════════════════════════════════════
    // CONDITIONS
    // ════════════════════════════════════════════════════════════════════

    /** Jour de préparation : J-7, ramené au vendredi s'il tombe un week-end. */
    public function prepareDay(StageRequest $req): ?CarbonImmutable
    {
        if (!$req->date_fin) {
            return null;
        }
        $d = CarbonImmutable::parse($req->date_fin->toDateString(), BusinessHours::TZ)->subDays(self::DAYS_BEFORE_END);
        while ($d->isWeekend()) {
            $d = $d->subDay();
        }

        return $d;
    }

    public function eligibility(StageRequest $req): array
    {
        $summary = $this->attendance->report($req)['summary'];
        $seuil   = (int) $this->settings->get('seuil_assiduite');
        $taux    = $summary['taux'];
        $final   = $this->reports->finalValidated($req);
        // Stage (en partie) antérieur au système : seule la période suivie compte ;
        // un stage déjà terminé à l'entrée dans le système n'a pas d'assiduité mesurable.
        $attOk   = ($taux !== null && $taux >= $seuil) || ($taux === null && $req->attendance_from !== null);

        $missing = [];
        if (!$attOk) {
            $missing[] = $taux === null
                ? 'Assiduité non encore mesurable.'
                : "Assiduité de {$taux} % (minimum {$seuil} %).";
        }
        if (!$final) {
            $missing[] = 'Rapport final pas encore validé.';
        }

        return [
            'taux'            => $taux,
            'seuil'           => $seuil,
            'attendance_ok'   => $attOk,
            'final_validated' => $final,
            'eligible'        => $attOk && $final,
            'jours_ouvres'    => $summary['jours_ouvres'],
            'absences'        => $summary['absences'],
            'missing'         => $missing,
            'computed_at'     => now()->toIso8601String(),
        ];
    }

    /** Contenu de l'attestation (données du stage + corrections du responsable). */
    public function content(StageRequest $req, ?array $overrides = null): array
    {
        $o = $overrides ?? [];
        $start = $req->date_debut_reelle ?? $req->date_debut;

        return [
            'civilite'       => $req->civilite,
            'nom'            => mb_strtoupper($req->nom),
            'prenoms'        => $req->prenoms,
            'nom_complet'    => trim($req->civilite . ' ' . $req->fullName()),
            'date_naissance' => $req->date_naissance?->toDateString(),
            'lieu_naissance' => $req->lieu_naissance,
            'nationalite'    => $req->nationalite,
            'etablissement'  => $req->etablissement_origine,
            'filiere'        => $req->filiere_niveau,
            'service'        => $o['service'] ?? $req->service,
            'lieu'           => $req->lieu,
            'date_debut'     => $start?->toDateString(),
            'date_fin'       => $req->date_fin?->toDateString(),
            'duree'          => $start && $req->date_fin ? $this->requests->durationLabel($start->toDateString(), $req->date_fin->toDateString()) : null,
            'theme'          => $o['theme'] ?? $req->theme_souhaite,
            // Champs du modèle officiel (modifiables par le responsable avant validation)
            'pays_naissance' => $o['pays_naissance'] ?? $this->defaultCountry($req->nationalite),
            'type_stage'     => $o['type_stage'] ?? 'académique',
            'structure'      => $o['structure'] ?? (string) $this->settings->get('attestation_structure'),
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // CIRCUIT
    // ════════════════════════════════════════════════════════════════════

    /**
     * Prépare une attestation (pré-remplie, à valider). Sans effet si une
     * attestation est déjà en cours : on la renvoie.
     */
    public function prepare(StageRequest $req, string $kind = 'normale', ?User $by = null, ?string $motifType = null, ?string $motif = null): StageAttestation
    {
        $created = false;
        $a = DB::transaction(function () use ($req, $kind, $by, $motifType, $motif, &$created) {
            $stage = StageRequest::whereKey($req->id)->lockForUpdate()->first();
            $this->assertStarted($stage);

            $open = StageAttestation::where('stage_request_id', $stage->id)->whereIn('status', self::OPEN)->first();
            if ($open) {
                return $open;
            }

            $a = StageAttestation::create([
                'stage_request_id' => $stage->id, 'kind' => $kind, 'status' => 'a_valider',
                'motif_type' => $motifType, 'motif' => $motif, 'created_by' => $by?->id,
            ]);
            if ($kind === 'normale' && !$stage->attestation_prepared_at) {
                $stage->forceFill(['attestation_prepared_at' => now()])->save();
            }
            $this->history($stage, 'attestation_prepared', $by, $kind === 'demande'
                ? 'Demandée par le stagiaire : ' . (self::MOTIF_TYPES[$motifType] ?? $motifType) . ($motif ? " - {$motif}" : '')
                : null, $kind === 'demande' ? 'demandeur' : null);
            $created = true;

            return $a;
        });

        if ($created) {
            $this->notifyPrepared($req->fresh(), $a);
        }

        return $a;
    }

    /** Le stagiaire demande une attestation (après son stage). */
    public function internRequest(StageRequest $req, string $motifType, ?string $motif): StageAttestation
    {
        if (!$req->date_fin || BusinessHours::today()->toDateString() < $req->date_fin->toDateString()) {
            throw new BusinessException('Vous pourrez demander une attestation à la fin de votre stage. Elle est préparée automatiquement une semaine avant.', 'STAGE_ATT_TOO_EARLY', 422);
        }
        $open = StageAttestation::where('stage_request_id', $req->id)->whereIn('status', self::OPEN)->first();
        if ($open) {
            throw new BusinessException('Votre attestation est déjà en cours de traitement (' . mb_strtolower(self::PUBLIC_LABELS[$open->status]) . ').', 'STAGE_ATT_OPEN', 409);
        }

        return $this->prepare($req, 'demande', null, $motifType, $motif);
    }

    /** Corrections du responsable avant validation (thème, service, observations). */
    public function edit(StageAttestation $a, array $overrides, User $by): StageAttestation
    {
        return DB::transaction(function () use ($a, $overrides, $by) {
            $a = StageAttestation::whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['a_valider']);
            $clean = [];
            foreach (array_keys(self::EDITABLE) as $k) {
                if (array_key_exists($k, $overrides)) {
                    $v = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $overrides[$k])));
                    $clean[$k] = $v === '' ? null : $v;
                }
            }
            $a->overrides = array_filter(array_merge($a->overrides ?? [], $clean), fn ($v) => $v !== null);
            $a->save();
            $this->history($a->stage, 'attestation_edited', $by, null);

            return $a;
        });
    }

    /** Validation par le responsable : les conditions doivent être remplies. */
    public function validate(StageAttestation $a, User $by): StageAttestation
    {
        $a = DB::transaction(function () use ($a, $by) {
            $a = StageAttestation::with('stage')->whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['a_valider']);
            $elig = $this->eligibility($a->stage);
            if (!$elig['eligible']) {
                throw new BusinessException(
                    'Conditions non remplies : ' . implode(' ', $elig['missing']) . ' Vous pouvez établir une attestation exceptionnelle (mot de passe et motif).',
                    'STAGE_ATT_NOT_ELIGIBLE', 422,
                );
            }
            $this->freeze($a, $elig, $by);
            $this->history($a->stage, 'attestation_validated', $by, null);

            return $a;
        });
        $this->notifySecretariat($a, 'Attestation de stage à imprimer');

        return $a;
    }

    /**
     * Attestation exceptionnelle : le responsable confirme avec son mot de
     * passe et donne un motif ; les conditions ne sont pas exigées.
     */
    public function exceptional(StageRequest $req, User $by, string $password, string $motif): StageAttestation
    {
        if (!Hash::check($password, (string) $by->getAuthPassword())) {
            throw new BusinessException('Mot de passe incorrect.', 'STAGE_ATT_PASSWORD', 422);
        }

        $a = DB::transaction(function () use ($req, $by, $motif) {
            $stage = StageRequest::whereKey($req->id)->lockForUpdate()->first();
            $this->assertStarted($stage);
            $open = StageAttestation::where('stage_request_id', $stage->id)->whereIn('status', self::OPEN)->lockForUpdate()->first();
            if ($open && $open->status !== 'a_valider') {
                throw new BusinessException('Une attestation est déjà validée pour ce stagiaire.', 'STAGE_ATT_OPEN', 409);
            }
            $a = $open ?? StageAttestation::create([
                'stage_request_id' => $stage->id, 'kind' => 'exceptionnelle', 'status' => 'a_valider', 'created_by' => $by->id,
            ]);
            $a->kind  = 'exceptionnelle';
            $a->motif = trim(($open?->motif ? $open->motif . "\n" : '') . 'Exceptionnelle : ' . $motif);
            $a->setRelation('stage', $stage);
            $this->freeze($a, $this->eligibility($stage), $by);
            if (!$stage->attestation_prepared_at) {
                $stage->forceFill(['attestation_prepared_at' => now()])->save();
            }
            $this->history($stage, 'attestation_exceptional', $by, $motif);

            return $a;
        });
        $this->notifySecretariat($a, 'Attestation de stage à imprimer');

        return $a;
    }

    /** Impression : le secrétariat confirme le numéro (proposé, modifiable). */
    public function markPrinted(StageAttestation $a, ?string $numero, ?string $date, User $by): StageAttestation
    {
        $numero = $numero !== null ? (trim(preg_replace('/\s+/u', ' ', $numero)) ?: null) : null;

        $first = false;
        $a = DB::transaction(function () use ($a, $numero, $date, $by, &$first) {
            $a = StageAttestation::with('stage')->whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['a_imprimer', 'imprimee']);
            $docDate = $this->documentDate($a, $date);
            if ($numero !== null) {
                $taken = StageAttestation::where('numero', $numero)->where('id', '!=', $a->id)
                    ->where('status', '!=', 'annulee')->where('stage_request_id', '!=', $a->stage_request_id)->exists();
                if ($taken) {
                    throw new BusinessException("Le numéro {$numero} est déjà utilisé pour un autre stagiaire.", 'STAGE_ATT_NUMBER_TAKEN', 409);
                }
            }
            $first = $a->status === 'a_imprimer';
            $a->forceFill([
                'numero' => $numero, 'status' => 'imprimee', 'document_date' => $docDate->toDateString(),
                'printed_by' => $first ? $by->id : $a->printed_by, 'printed_at' => $first ? now() : $a->printed_at,
            ])->save();
            $this->history($a->stage, $first ? 'attestation_printed' : 'attestation_number_changed', $by,
                trim(($numero ? "N° {$numero} - " : '') . 'datée du ' . $docDate->format('d/m/Y')));

            return $a;
        });

        if ($first) {
            $c = $a->content ?? [];
            $this->notifier->toDemandeur(
                $a->stage,
                'Votre attestation de stage est prête',
                ['Votre attestation de stage est prête. Vous pouvez la retirer au secrétariat du CAP-EPAC, aux heures d\'ouverture, muni(e) d\'une pièce d\'identité.'],
                ($a->numero ? ['Attestation n°' => $a->numero] : []) + (!empty($c['date_debut']) && !empty($c['date_fin'])
                    ? ['Stage' => 'Du ' . $this->requests->frDate($c['date_debut']) . ' au ' . $this->requests->frDate($c['date_fin'])] : []),
                context: 'stage-attestation-prete',
            );
        }

        return $a;
    }

    /**
     * Date portée sur l'attestation : celle choisie, sinon celle déjà
     * enregistrée, sinon aujourd'hui. Pas avant le début du stage, pas plus de
     * 60 jours dans le futur.
     */
    public function documentDate(StageAttestation $a, ?string $date): CarbonImmutable
    {
        $d = $date ? CarbonImmutable::parse($date, BusinessHours::TZ)->startOfDay()
            : ($a->document_date ? CarbonImmutable::parse($a->document_date->toDateString(), BusinessHours::TZ) : $this->defaultDocumentDate($a));
        $start = $a->stage->date_debut_reelle ?? $a->stage->date_debut;
        if ($start && $d->toDateString() < $start->toDateString()) {
            throw new BusinessException('La date de l\'attestation ne peut pas précéder le début du stage.', 'STAGE_ATT_DATE', 422);
        }
        if ($d->greaterThan(BusinessHours::today()->addDays(60))) {
            throw new BusinessException('La date de l\'attestation est trop éloignée.', 'STAGE_ATT_DATE', 422);
        }

        return $d;
    }

    /**
     * Date proposée par défaut : le jour même ; pour un stagiaire arrivé avant
     * le système, la fin de stage déclarée (aucune trace d'un enregistrement tardif).
     */
    private function defaultDocumentDate(StageAttestation $a): CarbonImmutable
    {
        $today = BusinessHours::today();
        $req   = $a->stage;
        if ($req->origin === 'invitation' && $req->date_fin && $req->date_fin->toDateString() < $today->toDateString()) {
            return CarbonImmutable::parse($req->date_fin->toDateString(), BusinessHours::TZ);
        }

        return $today;
    }

    /** Remise au stagiaire (datée). */
    public function markDelivered(StageAttestation $a, string $date, User $by): StageAttestation
    {
        return DB::transaction(function () use ($a, $date, $by) {
            $a = StageAttestation::with('stage')->whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['imprimee']);
            $d = CarbonImmutable::parse($date, BusinessHours::TZ)->startOfDay();
            if ($d->greaterThan(BusinessHours::today())) {
                throw new BusinessException('La date de remise ne peut pas être dans le futur.', 'STAGE_ATT_DATE', 422);
            }
            $printed = CarbonImmutable::parse($a->printed_at)->setTimezone(BusinessHours::TZ)->startOfDay();
            if ($d->lessThan($printed)) {
                throw new BusinessException('La date de remise ne peut pas précéder l\'impression (' . $this->requests->frDate($printed) . ').', 'STAGE_ATT_DATE', 422);
            }
            $a->forceFill(['status' => 'remise', 'delivered_on' => $d->toDateString(), 'delivered_by' => $by->id, 'delivered_at' => now()])->save();
            $this->history($a->stage, 'attestation_delivered', $by, 'Remise le ' . $this->requests->frDate($d));

            return $a;
        });
    }

    /** Scan de l'attestation signée (archive), facultatif ; remplace le précédent. */
    public function attachScan(StageAttestation $a, UploadedFile $file, User $by): StageAttestation
    {
        $this->assertStatus($a, ['imprimee', 'remise']);
        $ext  = strtolower($file->guessExtension() ?: 'bin');
        $path = $file->storeAs(
            "stages/{$a->stage->reference}/attestations",
            "attestation-{$a->id}-" . now()->format('YmdHis') . '-' . bin2hex(random_bytes(4)) . ".{$ext}",
            StageRequestService::DISK,
        );
        if (!$path) {
            throw new BusinessException('Le fichier n\'a pas pu être enregistré.', 'STAGE_ATT_SCAN', 500);
        }

        try {
            $old = DB::transaction(function () use ($a, $path, $file, $by) {
                $a = StageAttestation::with('stage')->whereKey($a->id)->lockForUpdate()->first();
                $this->assertStatus($a, ['imprimee', 'remise']);
                $old = $a->scan_path;
                $a->forceFill(['scan_path' => $path, 'scan_name' => mb_substr($file->getClientOriginalName(), 0, 200)])->save();
                $this->history($a->stage, 'attestation_scan', $by, null);

                return $old;
            });
        } catch (\Throwable $e) {
            Storage::disk(StageRequestService::DISK)->delete($path);
            throw $e;
        }
        if ($old && $old !== $path) {
            Storage::disk(StageRequestService::DISK)->delete($old);
        }

        return $a->fresh('stage');
    }

    public function cancel(StageAttestation $a, string $reason, User $by): StageAttestation
    {
        return DB::transaction(function () use ($a, $reason, $by) {
            $a = StageAttestation::with('stage')->whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, self::OPEN);
            $a->forceFill(['status' => 'annulee', 'cancelled_by' => $by->id, 'cancelled_at' => now(), 'cancel_reason' => $reason])->save();
            $this->history($a->stage, 'attestation_cancelled', $by, $reason);

            return $a;
        });
    }

    // ════════════════════════════════════════════════════════════════════
    // TÂCHE QUOTIDIENNE (jours ouvrés)
    // ════════════════════════════════════════════════════════════════════

    /** @return array{prepared: int, reminded: int} */
    public function tick(?CarbonImmutable $today = null): array
    {
        $today ??= BusinessHours::today();
        $stats = ['prepared' => 0, 'reminded' => 0];

        // Préparation à J-7 (rattrapage si la tâche n'est pas passée ce jour-là).
        $candidates = StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->whereNull('attestation_prepared_at')
            ->whereBetween('date_fin', [$today->subDays(60)->toDateString(), $today->addDays(self::DAYS_BEFORE_END + 3)->toDateString()])
            ->get();
        foreach ($candidates as $req) {
            try {
                $day = $this->prepareDay($req);
                if ($day && $today->toDateString() >= $day->toDateString()) {
                    $a = $this->prepare($req);
                    $stats['prepared'] += $a->wasRecentlyCreated ? 1 : 0;
                    if (!$a->wasRecentlyCreated) {
                        $req->forceFill(['attestation_prepared_at' => now()])->save();
                    }
                }
            } catch (\Throwable $e) {
                Log::error('[Stages] Préparation de l\'attestation impossible', ['reference' => $req->reference, 'error' => $e->getMessage()]);
            }
        }

        // Relances : une première fois, puis chaque lundi.
        $pending = StageAttestation::with('stage')->whereIn('status', ['a_valider', 'a_imprimer'])->get();
        foreach ($pending as $a) {
            try {
                if (!$this->dueForReminder($a, $today)) {
                    continue;
                }
                if ($a->status === 'a_valider') {
                    $elig = $this->eligibility($a->stage);
                    if (!$elig['eligible'] && $a->kind === 'normale') {
                        continue; // rien à décider tant que les conditions ne sont pas réunies
                    }
                    $this->notifier->toManagersGeneric('Attestation de stage en attente de validation', [
                        'Une attestation de stage attend votre validation.',
                        $elig['eligible'] ? 'Les conditions sont remplies.' : 'Conditions non remplies : ' . implode(' ', $elig['missing']),
                    ], ['Stagiaire' => $a->stage->fullName(), 'Fin du stage' => $this->requests->frDate($a->stage->date_fin)],
                        "stage-attestation-relance:{$a->stage->reference}", $this->staffUrl($a), $a->stage->reference);
                } else {
                    $this->notifySecretariat($a, 'Rappel : attestation de stage à imprimer');
                }
                $a->forceFill(['reminded_at' => now()])->save();
                $stats['reminded']++;
            } catch (\Throwable $e) {
                Log::error('[Stages] Relance d\'attestation impossible', ['id' => $a->id, 'error' => $e->getMessage()]);
            }
        }

        return $stats;
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE
    // ════════════════════════════════════════════════════════════════════

    /** Vue « stagiaire » côté personnel : conditions + attestations. */
    public function forStage(StageRequest $req): array
    {
        $list = StageAttestation::where('stage_request_id', $req->id)->orderByDesc('id')->get();

        return [
            'prepare_day' => $this->prepareDay($req)?->toDateString(),
            'eligibility' => $this->eligibility($req),
            'items'       => $list->map(fn ($a) => $this->present($a->setRelation('stage', $req)))->values(),
            'has_open'    => $list->contains(fn ($a) => in_array($a->status, self::OPEN, true)),
        ];
    }

    public function staffList(string $filter, bool $secretariat): Collection
    {
        $q = StageAttestation::with('stage')->orderByDesc('updated_at');
        $statuses = match ($filter) {
            'a_valider'  => ['a_valider'],
            'a_imprimer' => ['a_imprimer'],
            'imprimee'   => ['imprimee'],
            'remise'     => ['remise'],
            'annulee'    => ['annulee'],
            default      => array_keys(self::STATUS_LABELS),
        };
        if ($secretariat) {
            $statuses = array_values(array_intersect($statuses, ['a_imprimer', 'imprimee', 'remise']));
        }

        return $q->whereIn('status', $statuses ?: ['__none__'])->limit(500)->get()->map(fn ($a) => $this->present($a, false));
    }

    public function counts(bool $secretariat): array
    {
        $c = StageAttestation::select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');

        return collect(self::STATUS_LABELS)->keys()
            ->reject(fn ($s) => $secretariat && !in_array($s, ['a_imprimer', 'imprimee', 'remise'], true))
            ->mapWithKeys(fn ($s) => [$s => (int) ($c[$s] ?? 0)])->all();
    }

    public function present(StageAttestation $a, bool $full = true): array
    {
        $req  = $a->stage;
        $live = $a->status === 'a_valider';
        $names = User::whereIn('id', array_filter([$a->validated_by, $a->printed_by, $a->delivered_by, $a->cancelled_by, $a->created_by]))
            ->get(['id', 'first_name', 'last_name'])->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name . ' ' . $u->last_name)]);

        $out = [
            'id'           => $a->id,
            'kind'         => $a->kind,
            'kind_label'   => self::KIND_LABELS[$a->kind] ?? $a->kind,
            'status'       => $a->status,
            'status_label' => self::STATUS_LABELS[$a->status] ?? $a->status,
            'numero'       => $a->numero,
            'document_date' => $a->document_date?->toDateString(),
            'default_document_date' => $this->defaultDocumentDate($a)->toDateString(),
            'motif_type'   => $a->motif_type,
            'motif_type_label' => $a->motif_type ? (self::MOTIF_TYPES[$a->motif_type] ?? $a->motif_type) : null,
            'motif'        => $a->motif,
            'created_at'   => $a->created_at?->toIso8601String(),
            'updated_at'   => $a->updated_at?->toIso8601String(),
            'validated_at' => $a->validated_at?->toIso8601String(),
            'validated_by' => $names[$a->validated_by] ?? null,
            'printed_at'   => $a->printed_at?->toIso8601String(),
            'printed_by'   => $names[$a->printed_by] ?? null,
            'delivered_on' => $a->delivered_on?->toDateString(),
            'delivered_by' => $names[$a->delivered_by] ?? null,
            'cancelled_at' => $a->cancelled_at?->toIso8601String(),
            'cancelled_by' => $names[$a->cancelled_by] ?? null,
            'cancel_reason'=> $a->cancel_reason,
            'has_scan'     => (bool) $a->scan_path,
            'stage'        => $req ? [
                'id' => $req->id, 'reference' => $req->reference, 'nom_complet' => trim($req->civilite . ' ' . $req->fullName()),
                'date_fin' => $req->date_fin?->toDateString(), 'whatsapp' => $req->whatsapp,
            ] : null,
        ];
        if ($full && $req) {
            $out['content']     = $live ? $this->content($req, $a->overrides) : $a->content;
            $out['overrides']   = $a->overrides ?? (object) [];
            $out['eligibility'] = $live ? $this->eligibility($req) : $a->eligibility;
        }

        return $out;
    }

    /** Vue stagiaire : statut, sans données internes. */
    public function forIntern(StageRequest $req): array
    {
        $list  = StageAttestation::where('stage_request_id', $req->id)->where('status', '!=', 'annulee')->orderByDesc('id')->get();
        $today = BusinessHours::today()->toDateString();

        return [
            'prepare_day' => $this->prepareDay($req)?->toDateString(),
            'eligibility' => collect($this->eligibility($req))->only(['taux', 'seuil', 'attendance_ok', 'final_validated', 'eligible', 'missing'])->all(),
            'items'       => $list->map(fn ($a) => [
                'id' => $a->id, 'kind_label' => self::KIND_LABELS[$a->kind] ?? $a->kind,
                'status' => $a->status, 'status_label' => self::PUBLIC_LABELS[$a->status] ?? $a->status,
                'numero' => in_array($a->status, ['imprimee', 'remise'], true) ? $a->numero : null,
                'delivered_on' => $a->delivered_on?->toDateString(), 'created_at' => $a->created_at?->toIso8601String(),
            ])->values(),
            'can_request' => $req->date_fin && $today >= $req->date_fin->toDateString()
                && !$list->contains(fn ($a) => in_array($a->status, self::OPEN, true)),
            'motif_types' => self::MOTIF_TYPES,
        ];
    }

    public function scanPath(StageAttestation $a): ?string
    {
        return $a->scan_path && Storage::disk(StageRequestService::DISK)->exists($a->scan_path) ? $a->scan_path : null;
    }

    /** Numéro proposé : « 001-26 » (séquence annuelle), imprimé « N° 001-26/CAP/EPAC/UAC ». */
    public function nextNumber(): string
    {
        $yy  = BusinessHours::today()->format('y');
        $max = StageAttestation::where('numero', 'like', '%-' . $yy)
            ->pluck('numero')
            ->map(fn ($n) => preg_match('/^(\d+)-' . $yy . '$/', (string) $n, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT) . '-' . $yy;
    }

    private function defaultCountry(?string $nationalite): ?string
    {
        return $nationalite && preg_match('/b[ée]nin/iu', $nationalite) ? 'Rép. du BENIN' : null;
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function freeze(StageAttestation $a, array $elig, User $by): void
    {
        $a->forceFill([
            'status'       => 'a_imprimer',
            'content'      => $this->content($a->stage, $a->overrides),
            'eligibility'  => $elig,
            'validated_by' => $by->id,
            'validated_at' => now(),
            'reminded_at'  => null,
        ])->save();
    }

    private function dueForReminder(StageAttestation $a, CarbonImmutable $today): bool
    {
        if ($today->isWeekend()) {
            return false;
        }
        if ($a->reminded_at) {
            return $today->isMonday()
                && CarbonImmutable::parse($a->reminded_at)->setTimezone(BusinessHours::TZ)->toDateString() < $today->toDateString();
        }
        if ($a->status === 'a_imprimer') {
            // Deux jours ouvrés après la validation.
            $since = CarbonImmutable::parse($a->validated_at ?? $a->updated_at)->setTimezone(BusinessHours::TZ)->startOfDay();

            return $this->workingDaysBetween($since, $today) >= 2;
        }
        // À valider : à partir du dernier jour du stage.
        return $a->stage->date_fin && $today->toDateString() >= $a->stage->date_fin->toDateString();
    }

    private function workingDaysBetween(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $n = 0;
        for ($d = $from->addDay(); $d->lessThanOrEqualTo($to); $d = $d->addDay()) {
            $n += $d->isWeekend() ? 0 : 1;
        }

        return $n;
    }

    private function notifyPrepared(StageRequest $req, StageAttestation $a): void
    {
        $elig = $this->eligibility($req);
        $staffFields = ['Stagiaire' => $req->fullName(), 'Fin du stage' => $this->requests->frDate($req->date_fin)]
            + ['Assiduité' => $elig['taux'] !== null ? "{$elig['taux']} %" : '—', 'Rapport final' => $elig['final_validated'] ? 'validé' : 'pas encore validé'];

        if ($a->kind === 'demande') {
            $this->notifier->toFollowers($req, 'Demande d\'attestation de stage', [
                'Un ancien stagiaire demande une attestation de stage.',
            ], $staffFields + ['Motif' => (self::MOTIF_TYPES[$a->motif_type] ?? '') . ($a->motif ? " - {$a->motif}" : '')],
                'stage-attestation-demande', $this->staffUrl($a));

            return;
        }

        $this->notifier->toManagersGeneric('Attestation de stage à valider', [
            'Le stage se termine bientôt : l\'attestation est pré-remplie et attend votre validation.',
            $elig['eligible'] ? 'Les conditions sont déjà remplies.' : 'Conditions pas encore remplies : ' . implode(' ', $elig['missing']),
        ], $staffFields, "stage-attestation:{$req->reference}", $this->staffUrl($a), $req->reference);

        if ($a->kind === 'normale') {
            $this->notifier->toDemandeur(
                $req,
                'Votre attestation de stage',
                [
                    'Votre stage se termine le ' . $this->requests->frDate($req->date_fin) . '. Votre attestation est en préparation.',
                    "Pour l'obtenir : une assiduité d'au moins {$elig['seuil']} % et un rapport final validé.",
                ],
                ['Assiduité actuelle' => $elig['taux'] !== null ? "{$elig['taux']} %" : '—', 'Rapport final' => $elig['final_validated'] ? 'validé' : 'à déposer / en attente de validation'],
                ctaLabel: 'Mon espace stagiaire',
                ctaUrl: $this->notifier->spaceUrl('/stage'),
                context: 'stage-attestation-preparation',
            );
        }
    }

    private function notifySecretariat(StageAttestation $a, string $title): void
    {
        $recipients = StageAccess::secretaries();
        if ($recipients->isEmpty()) {
            Log::warning('[Stages] Aucun compte secrétariat à prévenir pour l\'attestation', ['id' => $a->id]);
            return;
        }
        $this->notifier->toStaff($recipients, $title, [
            'Une attestation de stage validée est à imprimer, puis à remettre au stagiaire.',
        ], ['Stagiaire' => $a->stage->fullName(), 'Type' => self::KIND_LABELS[$a->kind] ?? $a->kind],
            "stage-attestation-secretariat:{$a->stage->reference}", $this->staffUrl($a), $a->stage->reference);
    }

    private function staffUrl(StageAttestation $a): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages/attestations?attestation=' . $a->id;
    }

    private function assertStarted(?StageRequest $req): void
    {
        if (!$req || $req->status !== StageStatus::ACCEPTED || !$req->activated_at) {
            throw new BusinessException('Ce stage n\'a pas commencé : pas d\'attestation possible.', 'STAGE_ATT_NOT_STARTED', 409);
        }
    }

    private function assertStatus(StageAttestation $a, array $allowed): void
    {
        if (!in_array($a->status, $allowed, true)) {
            throw new BusinessException('Cette action n\'est plus possible : l\'attestation est « ' . mb_strtolower(self::STATUS_LABELS[$a->status] ?? $a->status) . ' ».', 'STAGE_ATT_STATUS', 409);
        }
    }

    private function history(StageRequest $req, string $action, ?User $by, ?string $comment, ?string $actor = null): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => $action, 'from_status' => $req->status, 'to_status' => $req->status,
            'actor' => $actor ?? ($by ? 'staff' : 'systeme'), 'user_id' => $by?->id, 'comment' => $comment,
        ]);
    }
}

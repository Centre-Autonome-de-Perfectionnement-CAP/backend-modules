<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Core\Services\Notifier;
use App\Modules\Core\Services\WhatsAppBridgeClient;
use App\Modules\Core\Support\LinkToken;
use App\Modules\Core\Support\WhatsAppText;
use App\Modules\Stages\Models\StageAttestation;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Models\StageWorkAttestation;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Attestations de travail : pour une personne ayant travaillé au CAP
 * (souvent un ancien stagiaire). Traitées comme les attestations de stage :
 *
 *  1. le responsable invite la personne par son numéro WhatsApp (lien à
 *     usage unique, 7 jours, sans compte), ou part du dossier d'un ancien
 *     stagiaire (lien envoyé, ou fiche remplie par lui-même) ;
 *  2. la personne indique son identité, son diplôme, la période, ses
 *     fonctions et ses tâches ;
 *  3. le responsable vérifie, corrige, complète, puis valide ;
 *  4. le secrétariat imprime (numéro du registre des attestations), fait
 *     signer le Chef CAP, prévient la personne qui vient la retirer, puis
 *     enregistre la remise.
 */
class WorkAttestationService
{
    public const TTL_DAYS = 7;

    public const STATUS_LABELS = [
        'invitee'    => 'Lien envoyé - en attente',
        'a_valider'  => 'À vérifier (responsable)',
        'a_imprimer' => 'À imprimer (secrétariat)',
        'imprimee'   => 'Imprimée - à signer, puis prévenir',
        'prete'      => 'Prête - intéressé prévenu',
        'remise'     => 'Remise',
        'annulee'    => 'Annulée',
    ];

    /** Visibles du secrétariat. */
    public const SECRETARIAT = ['a_imprimer', 'imprimee', 'prete', 'remise'];

    public const OPEN = ['invitee', 'a_valider', 'a_imprimer', 'imprimee', 'prete'];

    /** Natures proposées (champ libre accepté ; vide : sans mention). */
    public const NATURES = ['à titre bénévole', 'en qualité de vacataire', 'à temps partiel'];

    /** Limites : l'attestation tient toujours sur une page. */
    public const MAX_FONCTIONS = 5;
    public const MAX_TACHES = 8;
    public const MAX_FONCTION_LEN = 90;
    public const MAX_TACHE_LEN = 110;
    /** Fonctions et tâches réunies (au-delà, le texte ne tient plus sur une page). */
    public const MAX_TOTAL_LEN = 800;

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly Notifier $channels,
        private readonly WhatsAppBridgeClient $bridge,
        private readonly StageSettingsService $settings,
        private readonly AttestationService $attestations,
    ) {}

    public function ready(): bool
    {
        if (Cache::get('stage-work-attestations-ready')) {
            return true;
        }
        $ok = Schema::hasTable('stage_work_attestations');
        if ($ok) {
            Cache::put('stage-work-attestations-ready', true, 3600);
        }

        return $ok;
    }

    // ════════════════════════════════════════════════════════════════════
    // RESPONSABLE : invitation, dossier existant, vérification
    // ════════════════════════════════════════════════════════════════════

    /** Invite une personne par son numéro WhatsApp ($stage : ancien stagiaire, fiche préremplie). */
    public function invite(string $rawPhone, User $by, ?StageRequest $stage = null): StageWorkAttestation
    {
        $this->assertReady();
        $phone = $this->normalize($rawPhone);
        if (!$phone) {
            throw new BusinessException('Numéro WhatsApp invalide. Exemple attendu : 01 97 00 00 00.', 'STAGE_WORK_PHONE', 422);
        }
        $token = LinkToken::make();
        $a = DB::transaction(function () use ($phone, $by, $stage, $token) {
            // Un numéro ne garde qu'un lien valable : l'ancien est annulé.
            StageWorkAttestation::where('phone', $phone)->where('status', 'invitee')->lockForUpdate()->get()
                ->each(fn ($old) => $old->forceFill(['status' => 'annulee', 'token_hash' => null, 'cancelled_by' => $by->id,
                    'cancelled_at' => now(), 'cancel_reason' => 'Nouvelle invitation envoyée'])->save());

            return StageWorkAttestation::create($this->fromStage($stage) + [
                'status' => 'invitee', 'phone' => $phone, 'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(self::TTL_DAYS), 'send_count' => 1, 'last_sent_at' => now(),
                'created_by' => $by->id,
            ]);
        });
        $this->sendLink($a, $token);
        if ($stage) {
            $this->history($stage, 'work_attestation_invited', $by, 'Lien envoyé pour une attestation de travail');
        }

        return $a;
    }

    /** Dossier d'un ancien stagiaire : fiche préremplie, à compléter par le responsable. */
    public function draftFromStage(StageRequest $stage, User $by): StageWorkAttestation
    {
        $this->assertReady();
        $a = StageWorkAttestation::create($this->fromStage($stage) + [
            'status' => 'a_valider', 'phone' => $stage->whatsapp, 'created_by' => $by->id,
        ]);
        $this->history($stage, 'work_attestation_draft', $by, 'Attestation de travail préparée');

        return $a;
    }

    public function resend(StageWorkAttestation $a, User $by): void
    {
        $this->assertStatus($a, ['invitee']);
        $token = LinkToken::make();
        $a->forceFill([
            'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(self::TTL_DAYS),
            'send_count' => $a->send_count + 1, 'last_sent_at' => now(),
        ])->save();
        $this->sendLink($a, $token);
    }

    /**
     * Corrections du responsable. Une fiche encore en attente de la personne
     * passe « à vérifier » (le responsable la remplit lui-même, le lien ne
     * fonctionne plus).
     */
    public function update(StageWorkAttestation $a, array $data, User $by): StageWorkAttestation
    {
        return DB::transaction(function () use ($a, $data, $by) {
            $a = StageWorkAttestation::whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['invitee', 'a_valider']);
            $fill = $this->clean($data);
            if (array_key_exists('phone', $data)) {
                $phone = $data['phone'] ? $this->normalize((string) $data['phone']) : null;
                if ($data['phone'] && !$phone) {
                    throw new BusinessException('Numéro WhatsApp invalide.', 'STAGE_WORK_PHONE', 422);
                }
                $fill['phone'] = $phone;
            }
            if ($a->status === 'invitee') {
                $fill += ['status' => 'a_valider', 'token_hash' => null];
            }
            $a->forceFill($fill)->save();
            $this->assertDates($a);

            return $a;
        });
    }

    /** Validation : tout est vérifié ; le contenu est figé, le secrétariat est prévenu. */
    public function validate(StageWorkAttestation $a, User $by): StageWorkAttestation
    {
        $a = DB::transaction(function () use ($a, $by) {
            $a = StageWorkAttestation::whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['a_valider']);
            if ($missing = $this->missing($a)) {
                throw new BusinessException('À compléter avant de valider : ' . implode(', ', $missing) . '.', 'STAGE_WORK_INCOMPLETE', 422);
            }
            $this->assertFits($a);
            $this->assertDates($a);
            $a->forceFill(['status' => 'a_imprimer', 'content' => $this->content($a), 'validated_by' => $by->id, 'validated_at' => now()])->save();

            return $a;
        });
        $this->toSecretariat($a, 'Attestation de travail à imprimer',
            'Une attestation de travail validée est à imprimer, à faire signer par le Chef CAP, puis à remettre.');

        return $a;
    }

    public function cancel(StageWorkAttestation $a, string $reason, User $by): StageWorkAttestation
    {
        return DB::transaction(function () use ($a, $reason, $by) {
            $a = StageWorkAttestation::whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, self::OPEN);
            $a->forceFill(['status' => 'annulee', 'token_hash' => null, 'cancelled_by' => $by->id, 'cancelled_at' => now(), 'cancel_reason' => $reason])->save();

            return $a;
        });
    }

    // ════════════════════════════════════════════════════════════════════
    // SECRÉTARIAT : impression, intéressé prévenu, remise
    // ════════════════════════════════════════════════════════════════════

    /** Impression : numéro du registre des attestations (proposé, modifiable) et date du document. */
    public function markPrinted(StageWorkAttestation $a, ?string $numero, ?string $date, User $by): StageWorkAttestation
    {
        $numero = $numero !== null ? (trim(preg_replace('/\s+/u', ' ', $numero)) ?: null) : null;

        return DB::transaction(function () use ($a, $numero, $date, $by) {
            $a = StageWorkAttestation::whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['a_imprimer', 'imprimee', 'prete']);
            if ($numero !== null && $this->numberTaken($numero, $a->id)) {
                throw new BusinessException("Le numéro {$numero} est déjà utilisé par une autre attestation.", 'STAGE_ATT_NUMBER_TAKEN', 409);
            }
            $first = $a->status === 'a_imprimer';
            $a->forceFill([
                'numero' => $numero, 'document_date' => $this->documentDate($a, $date)->toDateString(),
                'status' => $first ? 'imprimee' : $a->status,
                'printed_by' => $first ? $by->id : $a->printed_by, 'printed_at' => $first ? now() : $a->printed_at,
            ])->save();

            return $a;
        });
    }

    /** Attestation signée : la personne est prévenue (WhatsApp et email) qu'elle peut venir la retirer. */
    public function notifyReady(StageWorkAttestation $a, User $by): StageWorkAttestation
    {
        $a = DB::transaction(function () use ($a, $by) {
            $a = StageWorkAttestation::whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['imprimee', 'prete']);
            $a->forceFill(['status' => 'prete', 'notified_by' => $by->id, 'notified_at' => now(), 'notify_count' => $a->notify_count + 1])->save();

            return $a;
        });
        $this->notifier->toPerson($this->who($a), 'Votre attestation de travail est prête', [
            'Votre attestation de travail est prête. Vous pouvez la retirer au secrétariat du CAP-EPAC, aux heures d\'ouverture, muni(e) d\'une pièce d\'identité.',
        ], array_filter(['Attestation n°' => $a->numero, 'Période' => 'Du ' . $this->longDate($a->date_debut) . ' au ' . $this->longDate($a->date_fin)]),
            context: 'stage-attestation-travail-prete', official: true);

        return $a;
    }

    public function markDelivered(StageWorkAttestation $a, string $date, User $by): StageWorkAttestation
    {
        return DB::transaction(function () use ($a, $date, $by) {
            $a = StageWorkAttestation::whereKey($a->id)->lockForUpdate()->first();
            $this->assertStatus($a, ['imprimee', 'prete']);
            $d = CarbonImmutable::parse($date, BusinessHours::TZ)->startOfDay();
            if ($d->greaterThan(BusinessHours::today())) {
                throw new BusinessException('La date de remise ne peut pas être dans le futur.', 'STAGE_ATT_DATE', 422);
            }
            if ($d->lessThan(CarbonImmutable::parse($a->printed_at)->setTimezone(BusinessHours::TZ)->startOfDay())) {
                throw new BusinessException('La date de remise ne peut pas précéder l\'impression.', 'STAGE_ATT_DATE', 422);
            }
            $a->forceFill(['status' => 'remise', 'delivered_on' => $d->toDateString(), 'delivered_by' => $by->id])->save();

            return $a;
        });
    }

    // ════════════════════════════════════════════════════════════════════
    // PERSONNE INVITÉE (lien public, sans compte)
    // ════════════════════════════════════════════════════════════════════

    public function findByToken(string $token): ?StageWorkAttestation
    {
        if (!$this->ready()) {
            return null;
        }
        $a = StageWorkAttestation::where('token_hash', hash('sha256', $token))->first();

        return $a && $a->status === 'invitee' && $a->expires_at && now()->lessThan($a->expires_at) ? $a : null;
    }

    /** Ce que la personne voit en ouvrant son lien (fiche préremplie s'il s'agit d'un ancien stagiaire). */
    public function publicView(StageWorkAttestation $a): array
    {
        return [
            'phone' => $this->maskPhone((string) $a->phone),
            'today' => BusinessHours::today()->toDateString(),
            'limits' => ['fonctions' => self::MAX_FONCTIONS, 'taches' => self::MAX_TACHES, 'fonction' => self::MAX_FONCTION_LEN, 'tache' => self::MAX_TACHE_LEN, 'total' => self::MAX_TOTAL_LEN],
            'values' => [
                'civilite' => $a->civilite, 'nom' => $a->nom, 'prenoms' => $a->prenoms, 'email' => $a->email, 'diplome' => $a->diplome,
                'date_debut' => $a->date_debut?->toDateString(), 'date_fin' => $a->date_fin?->toDateString(),
                'fonctions' => $a->fonctions ?? [], 'taches' => $a->taches ?? [],
            ],
        ];
    }

    /** La personne envoie sa fiche : le lien ne sert plus, le responsable est prévenu. */
    public function submit(string $token, array $data): StageWorkAttestation
    {
        $a = DB::transaction(function () use ($token, $data) {
            $a = StageWorkAttestation::where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if (!$a || $a->status !== 'invitee' || !$a->expires_at || now()->greaterThanOrEqualTo($a->expires_at)) {
                throw new BusinessException("Ce lien n'est plus valable. Demandez-en un nouveau au CAP-EPAC.", 'STAGE_WORK_LINK', 410);
            }
            $a->forceFill($this->clean($data) + ['status' => 'a_valider', 'token_hash' => null, 'submitted_at' => now()])->save();
            $this->assertDates($a);
            $this->assertFits($a);

            return $a;
        });
        try {
            $this->notifier->toManagersGeneric('Attestation de travail à vérifier', [
                $a->fullName() . ' a envoyé sa fiche : vérifiez-la, corrigez si besoin, puis validez-la pour l\'impression.',
            ], ['Personne' => trim($a->civilite . ' ' . $a->fullName()), 'Période' => 'Du ' . $this->longDate($a->date_debut) . ' au ' . $this->longDate($a->date_fin)],
                'stage-attestation-travail', $this->staffUrl($a));
        } catch (\Throwable $e) {
            Log::error('[Stages] Attestation de travail : responsable non prévenu', ['id' => $a->id, 'error' => $e->getMessage()]);
        }

        return $a;
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE
    // ════════════════════════════════════════════════════════════════════

    public function staffList(string $filter, bool $secretariat, ?string $q = null): Collection
    {
        $this->assertReady();
        $statuses = $filter === 'all' ? array_keys(self::STATUS_LABELS) : [$filter];
        if ($secretariat) {
            $statuses = array_values(array_intersect($statuses, self::SECRETARIAT));
        }
        $q = trim((string) $q);

        return StageWorkAttestation::whereIn('status', $statuses ?: ['__none__'])
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('nom', 'like', "%{$q}%")->orWhere('prenoms', 'like', "%{$q}%")
                ->orWhere('numero', 'like', "%{$q}%")->orWhere('phone', 'like', '%' . preg_replace('/\D/', '', $q) . '%')))
            ->orderByDesc('updated_at')->limit(500)->get()->map(fn ($a) => $this->present($a, false));
    }

    public function counts(bool $secretariat): array
    {
        if (!$this->ready()) {
            return [];
        }
        $c = StageWorkAttestation::select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');

        return collect(array_keys(self::STATUS_LABELS))
            ->reject(fn ($s) => $secretariat && !in_array($s, self::SECRETARIAT, true))
            ->mapWithKeys(fn ($s) => [$s => (int) ($c[$s] ?? 0)])->all();
    }

    public function find(int $id, bool $secretariat): StageWorkAttestation
    {
        $this->assertReady();
        $a = StageWorkAttestation::find($id);
        if (!$a || ($secretariat && !in_array($a->status, self::SECRETARIAT, true))) {
            throw new BusinessException('Attestation introuvable.', 'STAGE_WORK_NOT_FOUND', 404);
        }

        return $a;
    }

    public function present(StageWorkAttestation $a, bool $full = true): array
    {
        $names = $full ? User::whereIn('id', array_filter([$a->created_by, $a->validated_by, $a->printed_by, $a->notified_by, $a->delivered_by, $a->cancelled_by]))
            ->get(['id', 'first_name', 'last_name'])->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name . ' ' . $u->last_name)]) : collect();
        $out = [
            'id' => $a->id, 'status' => $a->status, 'status_label' => self::STATUS_LABELS[$a->status] ?? $a->status,
            'nom_complet' => trim($a->civilite . ' ' . $a->fullName()) ?: null, 'phone' => $a->phone,
            'numero' => $a->numero, 'date_debut' => $a->date_debut?->toDateString(), 'date_fin' => $a->date_fin?->toDateString(),
            'stage' => $a->stage_request_id ? ['id' => $a->stage_request_id, 'reference' => $a->stage?->reference] : null,
            'expires_at' => $a->expires_at?->toIso8601String(), 'notified_at' => $a->notified_at?->toIso8601String(),
            'delivered_on' => $a->delivered_on?->toDateString(), 'updated_at' => $a->updated_at?->toIso8601String(),
        ];
        if (!$full) {
            return $out;
        }
        $who = fn ($id) => $id ? ($names[$id] ?? null) : null;

        return $out + [
            'values' => [
                'civilite' => $a->civilite, 'nom' => $a->nom, 'prenoms' => $a->prenoms, 'email' => $a->email, 'phone' => $a->phone,
                'diplome' => $a->diplome, 'nature' => $a->nature, 'fonctions' => $a->fonctions ?? [], 'taches' => $a->taches ?? [],
                'date_debut' => $a->date_debut?->toDateString(), 'date_fin' => $a->date_fin?->toDateString(),
                'appreciation' => $a->appreciation,
            ],
            'missing' => $this->missing($a),
            'document_date' => $a->document_date?->toDateString(),
            'default_document_date' => BusinessHours::today()->toDateString(),
            'next_number' => in_array($a->status, ['a_imprimer'], true) && !$a->numero ? $this->attestations->nextNumber() : null,
            'send_count' => $a->send_count, 'last_sent_at' => $a->last_sent_at?->toIso8601String(),
            'submitted_at' => $a->submitted_at?->toIso8601String(), 'notify_count' => $a->notify_count,
            'created_by' => $who($a->created_by), 'validated_by' => $who($a->validated_by), 'validated_at' => $a->validated_at?->toIso8601String(),
            'printed_by' => $who($a->printed_by), 'printed_at' => $a->printed_at?->toIso8601String(),
            'notified_by' => $who($a->notified_by), 'delivered_by' => $who($a->delivered_by),
            'cancelled_by' => $who($a->cancelled_by), 'cancelled_at' => $a->cancelled_at?->toIso8601String(), 'cancel_reason' => $a->cancel_reason,
            'natures' => self::NATURES,
            'limits' => ['fonctions' => self::MAX_FONCTIONS, 'taches' => self::MAX_TACHES, 'fonction' => self::MAX_FONCTION_LEN, 'tache' => self::MAX_TACHE_LEN, 'total' => self::MAX_TOTAL_LEN],
        ];
    }

    /**
     * Texte de l'attestation : figé à la validation, sinon calculé (aperçu).
     *
     * @return array<string, mixed>
     */
    public function content(StageWorkAttestation $a): array
    {
        if ($a->content && !in_array($a->status, ['invitee', 'a_valider'], true)) {
            return $a->content;
        }
        $masculin = $a->civilite === 'M.';
        $t = fn (?string $s) => $this->typo((string) $s);
        $fonctions = array_map($t, $a->fonctions ?? []);
        $taches = array_map($t, $a->taches ?? []);
        $diplome = $t($a->diplome);

        return [
            'nom_complet' => trim($a->civilite . ' ' . $a->fullName()),
            'civilite'    => (string) $a->civilite,
            'masculin'    => $masculin,
            'nom'         => mb_strtoupper((string) $a->nom),
            'prenoms'     => $t($a->prenoms),
            // « du diplôme de Licence… », « du diplôme d’Ingénieur… »
            'diplome'     => $diplome !== '' ? $diplome : null,
            'diplome_de'  => preg_match('/^[aeiouyhâàéèêëîïôöûü]/iu', $diplome) ? 'd’' : 'de ',
            'nature'      => $t($a->nature) ?: null,
            'fonctions'   => $fonctions,
            'taches'      => $taches,
            'debut'       => $this->longDate($a->date_debut),
            'fin'         => $this->longDate($a->date_fin),
            'appreciation' => $t($a->appreciation) ?: null,
            // Contenu long : texte resserré pour tenir sur une page.
            'dense'       => $this->density($fonctions, $taches, $diplome . $a->appreciation),
        ];
    }

    /** Date portée sur le document : choisie, sinon enregistrée, sinon aujourd'hui (pas avant le début, pas plus de 60 jours plus tard). */
    public function documentDate(StageWorkAttestation $a, ?string $date): CarbonImmutable
    {
        $d = $date ? CarbonImmutable::parse($date, BusinessHours::TZ)->startOfDay()
            : ($a->document_date ? CarbonImmutable::parse($a->document_date->toDateString(), BusinessHours::TZ) : BusinessHours::today());
        if ($a->date_debut && $d->toDateString() < $a->date_debut->toDateString()) {
            throw new BusinessException('La date de l\'attestation ne peut pas précéder le début de la période.', 'STAGE_ATT_DATE', 422);
        }
        if ($d->greaterThan(BusinessHours::today()->addDays(60))) {
            throw new BusinessException('La date de l\'attestation est trop éloignée.', 'STAGE_ATT_DATE', 422);
        }

        return $d;
    }

    /** « 2 mars 2026 », « 1er avril 2026 ». */
    public function longDate(mixed $date): string
    {
        if (!$date) {
            return '…';
        }
        $d = CarbonImmutable::parse($date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date)->locale('fr');

        return ($d->day === 1 ? '1er' : (string) $d->day) . ' ' . $d->isoFormat('MMMM YYYY');
    }

    // ── Interne ──────────────────────────────────────────────────────────

    /** Classe de mise en page : '' (normal), 'dense' ou 'denser' selon la longueur du texte. */
    private function density(array $fonctions, array $taches, string $other): string
    {
        $lines = count($fonctions) + count($taches);
        $chars = mb_strlen(implode('', $fonctions) . implode('', $taches) . $other);

        return match (true) {
            $lines > 10 || $chars > 1250 => 'denser',
            $lines > 6 || $chars > 700   => 'dense',
            default                      => '',
        };
    }

    /** Champs à compléter avant la validation. */
    private function missing(StageWorkAttestation $a): array
    {
        return array_keys(array_filter([
            'la civilité' => !in_array($a->civilite, ['M.', 'Mme', 'Mlle'], true),
            'le nom' => trim((string) $a->nom) === '',
            'les prénoms' => trim((string) $a->prenoms) === '',
            'le diplôme' => trim((string) $a->diplome) === '',
            'la période' => !$a->date_debut || !$a->date_fin,
            'au moins une fonction' => count($a->fonctions ?? []) === 0,
        ]));
    }

    /** Tout doit tenir sur une page. */
    private function assertFits(StageWorkAttestation $a): void
    {
        if (mb_strlen(implode('', $a->fonctions ?? []) . implode('', $a->taches ?? [])) > self::MAX_TOTAL_LEN) {
            throw new BusinessException('Fonctions et tâches trop longues pour tenir sur une page : raccourcissez-les (' . self::MAX_TOTAL_LEN . ' caractères au plus en tout).', 'STAGE_WORK_TOO_LONG', 422);
        }
    }

    private function assertDates(StageWorkAttestation $a): void
    {
        if ($a->date_debut && $a->date_fin && $a->date_fin->lessThan($a->date_debut)) {
            throw new BusinessException('La date de fin doit suivre la date de début.', 'STAGE_WORK_DATES', 422);
        }
        if ($a->date_fin && $a->date_fin->toDateString() > BusinessHours::today()->toDateString()) {
            throw new BusinessException('La période doit être terminée : la date de fin ne peut pas être dans le futur.', 'STAGE_WORK_DATES', 422);
        }
    }

    /** Valeurs nettoyées (texte brut, listes sans lignes vides). */
    private function clean(array $data): array
    {
        $text = fn ($v) => $v === null ? null : (trim(preg_replace('/\s+/u', ' ', strip_tags((string) $v))) ?: null);
        $list = fn ($v) => array_values(array_filter(array_map($text, (array) $v)));
        $out = [];
        foreach (['civilite', 'email', 'diplome', 'nature', 'appreciation', 'date_debut', 'date_fin'] as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $text($data[$k]);
            }
        }
        if (array_key_exists('nom', $data)) {
            $out['nom'] = $text($data['nom']) !== null ? mb_strtoupper($text($data['nom'])) : null;
        }
        if (array_key_exists('prenoms', $data)) {
            $out['prenoms'] = $text($data['prenoms']);
        }
        if (isset($out['email'])) {
            $out['email'] = mb_strtolower($out['email']);
        }
        foreach (['fonctions', 'taches'] as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $list($data[$k]);
            }
        }

        return $out;
    }

    /** Apostrophes et espaces typographiques (ponctuation double précédée d'une espace insécable). */
    private function typo(string $s): string
    {
        $s = str_replace("'", '’', trim($s));

        return preg_replace('/\s*([:;!?])/u', "\u{00A0}$1", $s);
    }

    private function fromStage(?StageRequest $stage): array
    {
        if (!$stage) {
            return [];
        }

        return [
            'stage_request_id' => $stage->id, 'civilite' => $stage->civilite, 'nom' => mb_strtoupper((string) $stage->nom),
            'prenoms' => $stage->prenoms, 'email' => $stage->email,
            'appreciation' => $this->defaultAppreciation(),
        ];
    }

    public function defaultAppreciation(): string
    {
        return 'Son engagement, son professionnalisme et sa disponibilité ont grandement contribué au bon fonctionnement du '
            . ($this->settings->get('default_service') ?: 'Service Informatique') . ' du Centre.';
    }

    private function numberTaken(string $numero, int $exceptId): bool
    {
        return StageWorkAttestation::where('numero', $numero)->where('id', '!=', $exceptId)->where('status', '!=', 'annulee')->exists()
            || StageAttestation::where('numero', $numero)->where('status', '!=', 'annulee')->exists();
    }

    private function sendLink(StageWorkAttestation $a, string $token): void
    {
        $url = rtrim((string) config('app.frontend_url'), '/') . '/attestation-travail/' . $token;
        $this->channels->whatsapp($a->phone, WhatsAppText::compose('Votre attestation de travail', [
            'Bonjour' . ($a->civilite && $a->nom ? ' ' . trim($a->civilite . ' ' . $a->fullName()) : '') . ',',
            'Le CAP-EPAC prépare votre attestation de travail. Indiquez vos informations (identité, diplôme, période, fonctions et tâches) en ouvrant ce lien personnel (valable '
                . self::TTL_DAYS . " jours, utilisable une seule fois) :\n{$url}",
            'Aucun compte à créer. Le CAP vérifie vos informations, puis vous prévient quand l\'attestation est prête à retirer.',
            '_Si vous n\'attendiez pas ce message, ignorez-le._',
        ]), 'stage-attestation-travail-lien', 'Stages');
    }

    private function toSecretariat(StageWorkAttestation $a, string $title, string $text): void
    {
        $to = StageAccess::secretaries();
        if ($to->isEmpty()) {
            Log::warning('[Stages] Aucun compte secrétariat à prévenir (attestation de travail)', ['id' => $a->id]);

            return;
        }
        $this->notifier->toStaff($to, $title, [$text], ['Personne' => trim($a->civilite . ' ' . $a->fullName())],
            "stage-attestation-travail-secretariat:{$a->id}", $this->staffUrl($a));
    }

    private function who(StageWorkAttestation $a): array
    {
        return ['civilite' => $a->civilite, 'name' => $a->fullName(), 'phone' => $a->phone, 'email' => $a->email, 'reference' => 'AT-' . $a->id];
    }

    private function staffUrl(StageWorkAttestation $a): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages/attestations?type=travail&attestation=' . $a->id;
    }

    private function normalize(string $raw): ?string
    {
        $n = $this->bridge->normalizePhone(trim($raw));

        return $n ? '+' . ltrim($n, '+') : null;
    }

    private function maskPhone(string $phone): string
    {
        $d = preg_replace('/\D/', '', $phone);

        return strlen($d) >= 4 ? '••••••' . substr($d, -4) : $phone;
    }

    private function assertReady(): void
    {
        if (!$this->ready()) {
            throw new BusinessException('Les attestations de travail seront disponibles dans quelques minutes.', 'STAGE_WORK_NOT_READY', 503);
        }
    }

    private function assertStatus(StageWorkAttestation $a, array $allowed): void
    {
        if (!in_array($a->status, $allowed, true)) {
            throw new BusinessException('Cette action n\'est plus possible : l\'attestation est « ' . mb_strtolower(self::STATUS_LABELS[$a->status] ?? $a->status) . ' ».', 'STAGE_ATT_STATUS', 409);
        }
    }

    private function history(StageRequest $req, string $action, User $by, string $comment): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => $action, 'from_status' => $req->status, 'to_status' => $req->status,
            'actor' => 'staff', 'user_id' => $by->id, 'comment' => $comment,
        ]);
    }
}

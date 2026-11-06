<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageReportVersion;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\ReportPlan;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Rapports de stage.
 *
 *  - la structure impose la fréquence (hebdomadaire par défaut) ; le
 *    stagiaire dépose dans la période de soumission (après : accepté,
 *    marqué « en retard ») ;
 *  - chaque dépôt est une nouvelle version, l'historique est conservé ;
 *  - superviseurs et responsable commentent et valident ; le responsable a
 *    le dernier mot et peut revenir sur sa décision ;
 *  - un rapport est « validé » si le responsable l'a validé, ou, à défaut
 *    de décision du responsable, si un superviseur l'a validé.
 */
class ReportService
{
    public const STATUS_LABELS = [
        'submitted'           => 'Déposé - en attente de lecture',
        'supervisor_approved' => 'Validé par le superviseur',
        'supervisor_rejected' => 'À corriger (superviseur)',
        'approved'            => 'Validé',
        'rejected'            => 'À corriger',
    ];

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly StageSettingsService $settings,
        private readonly StageRequestService $requests,
    ) {}

    // ════════════════════════════════════════════════════════════════════
    // CALENDRIER
    // ════════════════════════════════════════════════════════════════════

    /** Périodes attendues + état de chaque rapport. */
    public function overview(StageRequest $req, ?CarbonImmutable $today = null): array
    {
        $today ??= BusinessHours::today();
        $plan  = $this->plan($req);
        $reports = StageReport::where('stage_request_id', $req->id)->get()->keyBy('period_key');

        $items = [];
        $nextShown = false;
        foreach ($plan as $p) {
            $report = $reports[$p['key']] ?? null;
            $opened = $today->toDateString() >= $p['opens'];
            if (!$opened && !$report) {
                // On montre seulement la prochaine période à venir.
                if ($nextShown) {
                    continue;
                }
                $nextShown = true;
            }
            $items[] = $p + [
                'state'  => $this->state($p, $report, $today),
                'report' => $report ? $this->summary($report) : null,
            ];
        }
        // Rapports déposés hors calendrier actuel (changement de fréquence) : gardés.
        foreach ($reports as $key => $report) {
            if (!collect($plan)->contains('key', $key)) {
                $items[] = [
                    'key' => $key, 'kind' => $report->kind, 'label' => $this->labelFor($report),
                    'start' => $report->period_start?->toDateString(), 'end' => $report->period_end?->toDateString(),
                    'opens' => null, 'closes' => null, 'state' => 'deposited', 'report' => $this->summary($report),
                ];
            }
        }

        $final = collect($items)->firstWhere('key', ReportPlan::FINAL_KEY);

        $partner = $this->partnerFor($req);

        return [
            'frequency' => $this->settings->get('report_frequency'),
            'binome'    => $partner ? ['name' => $partner->fullName()] : null,
            'items'     => array_values(array_filter($items, fn ($i) => $i['key'] !== ReportPlan::FINAL_KEY)),
            'final'     => $final,
            'stats'     => [
                'expected'  => collect($items)->whereIn('state', ['open', 'missing', 'deposited', 'validated', 'to_fix'])->count(),
                'deposited' => $reports->count(),
                'validated' => $reports->filter(fn ($r) => $this->isValidated($r))->count(),
                'missing'   => collect($items)->where('state', 'missing')->count(),
                'final_validated' => $final && $final['report'] && $final['report']['validated'],
            ],
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // BINÔME
    // ════════════════════════════════════════════════════════════════════

    /** Colonnes du rapport en binôme présentes (migration passée) ? */
    public function pairingReady(): bool
    {
        if (Cache::get('stage-report-pairing-ready')) {
            return true;
        }
        $ok = Schema::hasColumn('stage_reports', 'pair_report_id') && Schema::hasColumn('stage_report_versions', 'submitted_by')
            && Schema::hasColumn('stage_report_comments', 'stage_request_id');
        if ($ok) {
            Cache::put('stage-report-pairing-ready', true, 3600);
        }

        return $ok;
    }

    /** Binôme actuel du stagiaire (stage en cours), ou null. */
    public function partnerFor(StageRequest $req): ?StageRequest
    {
        if (!$req->team_id || !$this->pairingReady()) {
            return null;
        }

        return StageRequest::where('team_id', $req->team_id)->where('id', '!=', $req->id)
            ->where('status', StageStatus::ACCEPTED)->first();
    }

    /**
     * Rapport jumeau (binôme), tant que les deux stagiaires forment encore un
     * binôme ; sinon chacun garde son rapport, sans lien actif.
     */
    public function twinOf(StageReport $r, bool $lock = false): ?StageReport
    {
        if (!$r->pair_report_id || !$this->pairingReady()) {
            return null;
        }
        $q = StageReport::with('stage')->where('id', $r->pair_report_id)->where('pair_report_id', $r->id);
        $twin = $lock ? $q->lockForUpdate()->first() : $q->first();
        $mine = $r->stage;

        return $twin && $mine && $mine->team_id && $twin->stage && $twin->stage->team_id === $mine->team_id ? $twin : null;
    }

    /**
     * Pour un dépôt : [rapport jumeau existant, binôme, période du binôme],
     * ou [null, null, null] si le dépôt ne concerne que le stagiaire.
     */
    private function pairFor(StageRequest $req, ?StageReport $report, array $period, bool $forPair): array
    {
        $none = [null, null, null];
        if ($period['kind'] === 'final' || !$this->pairingReady()) {
            return $none;
        }
        if ($report && $report->pair_report_id) {
            $twin = $this->twinOf($report, true);

            return $twin ? [$twin, $twin->stage, collect($this->plan($twin->stage))->firstWhere('key', $period['key']) ?? $period] : $none;
        }
        if (!$forPair || !($partner = $this->partnerFor($req))) {
            return $none;
        }
        $partnerPeriod = collect($this->plan($partner))->firstWhere('key', $period['key']);
        if (!$partnerPeriod) {
            throw new BusinessException(
                'Cette période ne fait pas partie du stage de ' . $partner->fullName() . '. Décochez « Déposer pour mon binôme » pour déposer seulement le vôtre.',
                'STAGE_REPORT_PAIR_PERIOD', 422,
            );
        }
        if (StageReport::where('stage_request_id', $partner->id)->where('period_key', $period['key'])->lockForUpdate()->exists()) {
            throw new BusinessException(
                $partner->fullName() . ' a déjà déposé son propre rapport pour cette période. Décochez « Déposer pour mon binôme » pour déposer le vôtre.',
                'STAGE_REPORT_PAIR_EXISTS', 409,
            );
        }

        return [null, $partner, $partnerPeriod];
    }

    private function isLate(StageRequest $req, array $period, CarbonImmutable $today): bool
    {
        // Entré dans le système après l'échéance : pas « en retard ».
        return $today->toDateString() > $period['closes']
            && !($req->attendance_from && $req->attendance_from->toDateString() > $period['closes']);
    }

    private function newReport(StageRequest $req, array $period, bool $late): StageReport
    {
        return StageReport::create([
            'stage_request_id' => $req->id, 'kind' => $period['kind'], 'period_key' => $period['key'],
            'frequency' => $period['kind'] === 'final' ? null : $this->settings->get('report_frequency'),
            'period_start' => $period['start'], 'period_end' => $period['end'],
            'status' => 'submitted', 'late' => $late, 'submitted_at' => now(),
        ]);
    }

    private function addVersion(StageReport $report, StageRequest $by, string $path, string $name, int $size, ?string $note, bool $late, bool $postDefense): void
    {
        $version = $report->current_version + 1;
        StageReportVersion::create([
            'report_id' => $report->id, 'version' => $version, 'file_path' => $path, 'original_name' => $name,
            'size' => $size, 'note' => $note, 'late' => $late, 'post_defense' => $postDefense,
        ] + ($this->pairingReady() ? ['submitted_by' => $by->id] : []));
        if ($postDefense) {
            // La validation reste acquise (attestation comprise) : la version corrigée est relue à part.
            $report->fill(['current_version' => $version, 'correction_status' => 'pending'])->save();
        } else {
            $report->fill(['current_version' => $version, 'status' => 'submitted', 'submitted_at' => now()])->save();
        }
    }

    /** Nombre de rapports, un rapport de binôme comptant une seule fois. */
    public function countDistinct(\Illuminate\Database\Eloquent\Builder $q): int
    {
        if (!$this->pairingReady()) {
            return $q->count();
        }
        $rows = $q->get(['id', 'pair_report_id']);
        $ids = $rows->pluck('id')->flip();

        return $rows->reject(fn ($r) => $r->pair_report_id && isset($ids[$r->pair_report_id]) && $r->id > $r->pair_report_id)->count();
    }

    /** Le rapport final est-il validé ? (condition de l'attestation) */
    public function finalValidated(StageRequest $req): bool
    {
        $r = StageReport::where('stage_request_id', $req->id)->where('period_key', ReportPlan::FINAL_KEY)->first();

        return $r !== null && $this->isValidated($r);
    }

    // ════════════════════════════════════════════════════════════════════
    // DÉPÔT (stagiaire)
    // ════════════════════════════════════════════════════════════════════

    /**
     * Dépôt d'une version. $forPair : le stagiaire dépose aussi pour son
     * binôme (rapports périodiques seulement) ; une fois liés, chaque nouvelle
     * version vaut pour les deux.
     */
    public function submit(StageRequest $req, string $periodKey, UploadedFile $file, ?string $note, bool $forPair = false): StageReport
    {
        $today = BusinessHours::today();
        $period = collect($this->plan($req))->firstWhere('key', $periodKey);
        if (!$period) {
            throw new BusinessException('Cette période de rapport n\'existe pas.', 'STAGE_REPORT_PERIOD', 422);
        }
        if ($today->toDateString() < $period['opens']) {
            throw new BusinessException('Le dépôt de ce rapport ouvre le ' . $this->requests->frDate($period['opens']) . '.', 'STAGE_REPORT_NOT_OPEN', 422);
        }
        $late = $this->isLate($req, $period, $today);

        $stored = null;
        $postDefense = false;
        $twin = null;
        try {
            $report = DB::transaction(function () use ($req, $period, $file, $note, $forPair, $today, &$late, &$stored, &$postDefense, &$twin) {
                $report = StageReport::where('stage_request_id', $req->id)->where('period_key', $period['key'])->lockForUpdate()->first();
                // Rapport final validé : seule une version corrigée après la
                // soutenance est encore possible (dans le délai prévu).
                $postDefense = $report && $this->isValidated($report);
                if ($postDefense) {
                    if (!$this->canSubmitCorrection($report)) {
                        throw new BusinessException(
                            $report->correction_status === 'pending'
                                ? 'Votre version corrigée est en cours de relecture.'
                                : 'Ce rapport est déjà validé : il ne peut plus être modifié.',
                            'STAGE_REPORT_LOCKED', 409,
                        );
                    }
                    $late = false;
                }
                [$twin, $partner, $partnerPeriod] = $this->pairFor($req, $report, $period, $forPair);
                if (!$report) {
                    $report = $this->newReport($req, $period, $late);
                }
                $version = $report->current_version + 1;
                $stored = $file->storeAs("stages/{$req->reference}/rapports", strtolower($period['key']) . "-v{$version}.pdf", StageRequestService::DISK);
                if (!$stored) {
                    throw new \RuntimeException('Enregistrement du fichier impossible');
                }
                $name = mb_substr(preg_replace('/[^\pL\pN\s._-]/u', '', $file->getClientOriginalName()) ?: 'rapport.pdf', 0, 200);
                $this->addVersion($report, $req, $stored, $name, (int) $file->getSize(), $note, $late, $postDefense);
                $this->history($req, 'report_submitted', null, $this->labelFor($report) . " - version {$report->current_version}"
                    . ($postDefense ? ' corrigée après soutenance' : ($late ? ' (en retard)' : '')) . ($partner ? ', pour le binôme' : ''));

                // Binôme : le même fichier vaut pour le rapport jumeau.
                if ($partner) {
                    $twinLate = $this->isLate($partner, $partnerPeriod, $today);
                    if (!$twin) {
                        $twin = $this->newReport($partner, $partnerPeriod, $twinLate);
                        $twin->pair_report_id = $report->id;
                        $report->pair_report_id = $twin->id;
                        $report->save();
                    }
                    $this->addVersion($twin, $req, $stored, $name, (int) $file->getSize(), $note, $twinLate, false);
                    $this->history($partner, 'report_submitted', null, $this->labelFor($twin) . " - version {$twin->current_version}, déposée par " . $req->fullName() . ' pour le binôme');
                }

                return $report;
            });
        } catch (\Throwable $e) {
            if ($stored) {
                Storage::disk(StageRequestService::DISK)->delete($stored);
            }
            throw $e;
        }

        $partner = $twin?->stage;
        // Rapports journaliers : récapitulatif quotidien au personnel (pas un message par dépôt).
        if ($report->kind === 'final' || $report->frequency !== 'journalier') {
            $this->notifier->toFollowers(
                $partner ? [$req, $partner] : $req,
                $postDefense ? 'Rapport final corrigé après soutenance' : ($report->kind === 'final' ? 'Rapport final déposé' : 'Rapport déposé'),
                [$postDefense
                    ? 'Le stagiaire a déposé la version corrigée de son rapport final, après la soutenance. Sa validation précédente reste acquise.'
                    : ($partner
                        ? 'Le stagiaire a déposé le rapport pour lui et son binôme (' . $partner->fullName() . ').'
                        : ($report->current_version > 1 ? 'Le stagiaire a déposé une nouvelle version de son rapport.' : 'Le stagiaire a déposé son rapport.'))],
                ['Stagiaire' => $req->fullName(), 'Binôme' => $partner?->fullName(), 'Rapport' => $this->labelFor($report),
                    'Version' => (string) $report->current_version, 'Dépôt' => $late ? 'En retard' : null],
                'stage-rapport',
                $this->staffUrl($report),
            );
        }
        if ($partner) {
            $this->notifier->toDemandeur($partner, 'Rapport déposé pour votre binôme', [
                $req->fullName() . ' a déposé le rapport pour vous deux. Vous le retrouvez dans vos rapports ; les commentaires et la décision du superviseur valent pour vous deux.',
            ], ['Rapport' => $this->labelFor($twin), 'Version' => (string) $twin->current_version],
                ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-binome');
        }

        return $report->fresh();
    }

    // ════════════════════════════════════════════════════════════════════
    // COMMENTAIRES ET DÉCISIONS
    // ════════════════════════════════════════════════════════════════════

    /**
     * Commentaire. Rapport de binôme : le message figure sur les deux
     * rapports et les deux stagiaires sont prévenus.
     */
    public function comment(StageReport $report, string $body, ?User $staff): void
    {
        $body = trim(strip_tags($body));
        if ($body === '') {
            throw new BusinessException('Le commentaire est vide.', 'STAGE_REPORT_EMPTY_COMMENT', 422);
        }
        $req = $report->stage;
        $twin = $this->twinOf($report);
        DB::transaction(function () use ($report, $twin, $body, $staff, $req) {
            foreach (array_filter([$report, $twin]) as $r) {
                DB::table('stage_report_comments')->insert([
                    'report_id' => $r->id, 'version' => $r->current_version,
                    'author' => $staff ? 'staff' : 'stagiaire', 'user_id' => $staff?->id, 'body' => $body, 'created_at' => now(),
                ] + (!$staff && $this->pairingReady() ? ['stage_request_id' => $req->id] : []));
            }
        });

        if ($staff) {
            foreach (array_filter([$report, $twin]) as $r) {
                $this->notifier->toDemandeur($r->stage, 'Commentaire sur votre rapport', [
                    trim($staff->first_name . ' ' . $staff->last_name) . ' a commenté ' . ($twin ? 'le rapport de votre binôme.' : 'votre rapport.'),
                ], ['Rapport' => $this->labelFor($r)], highlight: $body, highlightTitle: 'Commentaire',
                    ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-commentaire');
            }
        } else {
            $this->notifier->toFollowers($twin ? [$req, $twin->stage] : $req, 'Réponse du stagiaire', [
                'Le stagiaire a répondu sur son rapport.',
            ], ['Stagiaire' => $req->fullName(), 'Binôme' => $twin?->stage->fullName(), 'Rapport' => $this->labelFor($report), 'Message' => $body],
                'stage-rapport', $this->staffUrl($report));
            if ($twin) {
                $this->notifier->toDemandeur($twin->stage, 'Message sur votre rapport de binôme', [
                    $req->fullName() . ' a écrit sur votre rapport commun.',
                ], ['Rapport' => $this->labelFor($twin)], highlight: $body, highlightTitle: 'Message',
                    ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-commentaire');
            }
        }
    }

    public function decide(StageReport $report, string $decision, ?string $comment, User $by): StageReport
    {
        if ($report->current_version < 1) {
            throw new BusinessException('Aucune version à examiner.', 'STAGE_REPORT_EMPTY', 409);
        }
        if ($decision === 'rejected' && !$comment) {
            throw new BusinessException('Indiquez ce qui doit être corrigé.', 'STAGE_REPORT_REJECT_COMMENT', 422);
        }
        $lead = StageAccess::isLead($by);
        $role = $lead ? 'responsable' : 'superviseur';

        if ($report->correction_status === 'pending') {
            return $this->decideCorrection($report, $decision, $comment, $by, $lead, $role);
        }

        $before = $this->isValidated($report);
        if (!$lead && $this->leadDecision($report) !== null) {
            throw new BusinessException('Le responsable de division a déjà tranché sur cette version.', 'STAGE_REPORT_LEAD_DECIDED', 409);
        }

        // Rapport de binôme : la décision vaut pour les deux rapports.
        $twin = $this->twinOf($report);
        $both = array_filter([$report, $twin]);
        DB::transaction(function () use ($both, $decision, $comment, $by, $role) {
            foreach ($both as $r) {
                DB::table('stage_report_reviews')->insert([
                    'report_id' => $r->id, 'version' => $r->current_version, 'user_id' => $by->id,
                    'role' => $role, 'decision' => $decision, 'comment' => $comment, 'created_at' => now(),
                ]);
                $r->status = $role === 'responsable' ? $decision : 'supervisor_' . $decision;
                if ($this->isValidated($r)) {
                    $r->official_version = $r->current_version;
                }
                $r->save();
                $this->history($r->stage, 'report_' . $decision, $by, $this->labelFor($r) . ($comment ? " : {$comment}" : ''));
            }
        });

        $req = $report->stage;
        $who = trim($by->first_name . ' ' . $by->last_name);
        foreach ($both as $r) {
            $this->notifier->toDemandeur(
                $r->stage,
                $decision === 'approved' ? 'Rapport validé' : 'Rapport à corriger',
                [$decision === 'approved'
                    ? "{$who} a validé " . ($twin ? 'le rapport de votre binôme.' : 'votre rapport.')
                    : "{$who} vous demande de corriger " . ($twin ? 'le rapport de votre binôme' : 'votre rapport') . " et d'en déposer une nouvelle version."],
                ['Rapport' => $this->labelFor($r)],
                highlight: $comment, highlightTitle: 'Commentaire',
                ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-decision',
            );
        }
        if (!$lead) {
            $this->notifier->toManagers($req, 'Action d\'un superviseur', [
                "{$who} (superviseur) a " . ($decision === 'approved' ? 'validé' : 'demandé la correction d\'') . 'un rapport. Vous pouvez confirmer ou revenir sur cette décision.',
            ], ['Stagiaire' => $req->fullName(), 'Binôme' => $twin?->stage->fullName(), 'Rapport' => $this->labelFor($report)], 'stage-superviseur');
        }
        if ($report->kind === 'final' && !$before && $this->isValidated($report->fresh())) {
            Log::info('[Stages] Rapport final validé', ['reference' => $req->reference]);
        }

        return $report->fresh();
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE
    // ════════════════════════════════════════════════════════════════════

    public function detail(StageReport $report, bool $forStaff): array
    {
        $pairing = $this->pairingReady();
        $comments = DB::table('stage_report_comments as c')->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->where('c.report_id', $report->id)->orderBy('c.id')
            ->get(array_merge(['c.id', 'c.version', 'c.author', 'c.body', 'c.created_at', 'u.first_name', 'u.last_name'], $pairing ? ['c.stage_request_id'] : []));
        $versions = $report->versions()->get();
        // Rapport de binôme : nom du stagiaire qui a déposé ou écrit.
        $interns = $pairing ? StageRequest::whereIn('id', $comments->pluck('stage_request_id')->merge($versions->pluck('submitted_by'))->filter()->unique())
            ->get()->mapWithKeys(fn ($r) => [$r->id => $r->fullName()]) : collect();
        $ownerId = $report->stage_request_id;
        $reviews = DB::table('stage_report_reviews as r')->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.report_id', $report->id)->orderBy('r.id')
            ->get(['r.id', 'r.version', 'r.role', 'r.decision', 'r.comment', 'r.created_at', 'u.first_name', 'u.last_name']);

        return $this->summary($report) + [
            'stage' => $forStaff ? [
                'id' => $report->stage->id, 'reference' => $report->stage->reference,
                'nom_complet' => trim($report->stage->civilite . ' ' . $report->stage->fullName()),
            ] : null,
            'versions' => $versions->map(fn ($v) => [
                'version' => $v->version, 'name' => $v->original_name, 'size' => $v->size, 'note' => $v->note,
                'late' => $v->late, 'post_defense' => (bool) $v->post_defense, 'at' => $v->created_at?->toIso8601String(),
                'by' => $v->submitted_by && $v->submitted_by !== $ownerId ? ($interns[$v->submitted_by] ?? null) : null,
            ])->values(),
            'comments' => $comments->map(fn ($c) => [
                'id' => $c->id, 'version' => $c->version, 'author' => $c->author,
                'mine' => $c->author === 'stagiaire' && (empty($c->stage_request_id) || (int) $c->stage_request_id === $ownerId),
                'name' => $c->author === 'staff' ? trim($c->first_name . ' ' . $c->last_name)
                    : (!empty($c->stage_request_id) ? ($interns[$c->stage_request_id] ?? 'Stagiaire') : 'Stagiaire'),
                'body' => $c->body, 'at' => CarbonImmutable::parse($c->created_at)->toIso8601String(),
            ])->values(),
            'reviews' => $reviews->map(fn ($r) => [
                'id' => $r->id, 'version' => $r->version, 'role' => $r->role, 'decision' => $r->decision, 'comment' => $r->comment,
                'name' => trim($r->first_name . ' ' . $r->last_name), 'at' => CarbonImmutable::parse($r->created_at)->toIso8601String(),
            ])->values(),
        ];
    }

    public function summary(StageReport $r): array
    {
        return [
            'id'              => $r->id,
            'kind'            => $r->kind,
            'period_key'      => $r->period_key,
            'label'           => $this->labelFor($r),
            'status'          => $r->status,
            'status_label'    => self::STATUS_LABELS[$r->status] ?? $r->status,
            'validated'       => $this->isValidated($r),
            'locked'          => $this->isValidated($r) && !$this->canSubmitCorrection($r),
            'current_version' => $r->current_version,
            'official_version'  => $r->official_version,
            'correction_status' => $r->correction_status,
            'can_submit_correction' => $this->canSubmitCorrection($r),
            'late'            => $r->late,
            'submitted_at'    => $r->submitted_at?->toIso8601String(),
            'binome'          => $this->pairInfo($r),
        ];
    }

    /** Rapport de binôme : nom du binôme et de qui a déposé la dernière version. */
    private function pairInfo(StageReport $r): ?array
    {
        if (!$r->pair_report_id || !($twin = $this->twinOf($r))) {
            return null;
        }
        $by = StageReportVersion::where('report_id', $r->id)->where('version', $r->current_version)->value('submitted_by');

        return [
            'report_id'    => $twin->id,
            'name'         => $twin->stage->fullName(),
            'deposited_by' => $by && (int) $by !== $r->stage_request_id ? $twin->stage->fullName() : null,
        ];
    }

    /** Liste pour le personnel. */
    public function staffList(string $filter, ?User $mineOf): Collection
    {
        $q = StageReport::with('stage')->orderByDesc('submitted_at')->limit(300);
        if ($filter === 'to_review') {
            $q->where(fn ($w) => $w->whereIn('status', ['submitted', 'supervisor_approved', 'supervisor_rejected'])
                ->orWhere('correction_status', 'pending'));
        } elseif ($filter !== 'all') {
            $q->where('status', $filter);
        }
        if ($mineOf) {
            $q->whereIn('stage_request_id', DB::table('stage_supervisions')->where('user_id', $mineOf->id)->select('stage_request_id'));
        }

        $rows = $q->get();
        // Rapport de binôme : une seule ligne pour les deux stagiaires.
        $ids = $rows->pluck('id')->flip();
        $rows = $rows->reject(fn ($r) => $r->pair_report_id && isset($ids[$r->pair_report_id]) && $r->id > $r->pair_report_id && $this->twinOf($r));

        return $rows->map(fn ($r) => $this->summary($r) + [
            'stage' => ['id' => $r->stage->id, 'reference' => $r->stage->reference, 'nom_complet' => trim($r->stage->civilite . ' ' . $r->stage->fullName())],
        ]);
    }

    public function filePath(StageReport $report, int $version): ?string
    {
        $v = StageReportVersion::where('report_id', $report->id)->where('version', $version)->first();

        return $v && Storage::disk(StageRequestService::DISK)->exists($v->file_path) ? $v->file_path : null;
    }

    // ════════════════════════════════════════════════════════════════════
    // RAPPELS (tâche quotidienne)
    // ════════════════════════════════════════════════════════════════════

    /** @return array{opened: int, mid: int, closing: int, late: int, digest: int} */
    public function tick(?CarbonImmutable $today = null, bool $reminders = true, bool $digest = true): array
    {
        $today ??= BusinessHours::today();
        $stats = ['opened' => 0, 'mid' => 0, 'closing' => 0, 'late' => 0, 'digest' => 0];

        $interns = !$reminders ? collect() : StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->where('date_fin', '>=', $today->subDays(10)->toDateString())->get();

        foreach ($interns as $req) {
            try {
                $reports = StageReport::where('stage_request_id', $req->id)->pluck('period_key')->all();
                foreach ($this->plan($req) as $p) {
                    if (in_array($p['key'], $reports, true)) {
                        continue;
                    }
                    // Rappels dans la période imposée : ouverture, mi-parcours
                    // (rapport final), dernier jour, puis retard (une fois).
                    $type = $this->reminderDue($p, $today);
                    if ($type && $this->notice($req, $p['key'], $type)) {
                        $this->remindIntern($req, $p, $type);
                        $stats[$type === 'open' ? 'opened' : ($type === 'closing' ? 'closing' : $type)]++;
                    }
                }
            } catch (\Throwable $e) {
                Log::error('[Stages] Rappel rapport impossible', ['reference' => $req->reference, 'error' => $e->getMessage()]);
            }
        }

        if (!$digest) {
            return $stats;
        }

        // Récapitulatif des rapports journaliers déposés aujourd'hui.
        $daily = StageReport::with('stage')->where('frequency', 'journalier')
            ->where('submitted_at', '>=', $today->utc())->get();
        if ($daily->isNotEmpty()) {
            $url  = rtrim((string) config('app.url'), '/') . '/services/stages/rapports';
            $text = fn (Collection $reports) => [$reports->count() . ' rapport(s) journalier(s) déposé(s) aujourd\'hui.'];
            $list = fn (Collection $reports) => $reports->groupBy('stage_request_id')
                ->mapWithKeys(fn ($g) => [$g->first()->stage->fullName() => $g->count() . ' rapport(s)'])->all();

            // Responsables : tous les stagiaires ; chaque superviseur : les siens.
            $this->notifier->toManagersGeneric('Rapports journaliers du jour', $text($daily), $list($daily), 'stage-rapports-journaliers', $url);
            $managerIds = StageAccess::managers()->pluck('id');
            $links = DB::table('stage_supervisions as s')->join('users as u', 'u.id', '=', 's.user_id')
                ->whereIn('s.stage_request_id', $daily->pluck('stage_request_id')->unique())
                ->whereNull('u.deleted_at')->whereNotIn('u.id', $managerIds)
                ->get(['s.stage_request_id', 'u.id', 'u.email', 'u.phone', 'u.first_name', 'u.last_name']);
            foreach ($links->groupBy('id') as $own) {
                $mine = $daily->whereIn('stage_request_id', $own->pluck('stage_request_id'));
                $this->notifier->toStaff(collect([$own->first()]), 'Rapports journaliers du jour', $text($mine), $list($mine), 'stage-rapports-journaliers', $url);
            }
            $stats['digest'] = $daily->count();
        }

        return $stats;
    }

    // ── Interne ──────────────────────────────────────────────────────────

    public function plan(StageRequest $req): array
    {
        $start = $req->date_debut_reelle ?? $req->date_debut;
        if (!$start || !$req->date_fin) {
            return [];
        }

        $periods = ReportPlan::periods(
            CarbonImmutable::parse($start->toDateString()),
            CarbonImmutable::parse($req->date_fin->toDateString()),
            (string) $this->settings->get('report_frequency'),
            (int) $this->settings->get('report_grace_days'),
            (int) $this->settings->get('final_report_open_days'),
        );

        // Stagiaire arrivé avant le système : pas de rapports périodiques pour
        // les périodes terminées avant son entrée ; le rapport final reste dû.
        if ($req->attendance_from) {
            $from = $req->attendance_from->toDateString();
            $periods = array_values(array_filter($periods, fn ($p) => $p['kind'] === 'final' || ($p['end'] ?? '') >= $from));
        }

        return $periods;
    }

    /**
     * Version corrigée après soutenance possible ? Rapport final validé, pas de
     * version corrigée en cours de relecture, et dans le délai après la fin du stage.
     */
    public function canSubmitCorrection(StageReport $r): bool
    {
        if ($r->kind !== 'final' || !$this->isValidated($r) || $r->correction_status === 'pending') {
            return false;
        }
        $end = $r->stage?->date_fin;
        $days = (int) $this->settings->get('final_correction_days');

        return $end !== null && $days > 0
            && BusinessHours::today()->toDateString() <= CarbonImmutable::parse($end->toDateString())->addDays($days)->toDateString();
    }

    /** Relecture d'une version corrigée après soutenance (la validation du rapport reste acquise). */
    private function decideCorrection(StageReport $report, string $decision, ?string $comment, User $by, bool $lead, string $role): StageReport
    {
        if (!$lead && DB::table('stage_report_reviews')->where('report_id', $report->id)->where('version', $report->current_version)
            ->where('role', 'responsable')->exists()) {
            throw new BusinessException('Le responsable de division a déjà tranché sur cette version.', 'STAGE_REPORT_LEAD_DECIDED', 409);
        }

        DB::transaction(function () use ($report, $decision, $comment, $by, $role, $lead) {
            DB::table('stage_report_reviews')->insert([
                'report_id' => $report->id, 'version' => $report->current_version, 'user_id' => $by->id,
                'role' => $role, 'decision' => $decision, 'comment' => $comment, 'created_at' => now(),
            ]);
            // Le superviseur donne son avis ; le responsable tranche.
            if ($lead) {
                $report->correction_status = $decision;
                if ($decision === 'approved') {
                    $report->official_version = $report->current_version;
                }
                $report->save();
            }
            $this->history($report->stage, 'report_correction_' . $decision, $by,
                $this->labelFor($report) . ' - version corrigée après soutenance' . ($comment ? " : {$comment}" : ''));
        });

        $req = $report->stage;
        $who = trim($by->first_name . ' ' . $by->last_name);
        if ($lead) {
            $this->notifier->toDemandeur(
                $req,
                $decision === 'approved' ? 'Version corrigée validée' : 'Version corrigée non retenue',
                [$decision === 'approved'
                    ? "{$who} a validé la version corrigée de votre rapport final. C'est désormais la version officielle."
                    : "{$who} n'a pas retenu la version corrigée de votre rapport final. Votre version validée précédente reste valable ; vous pouvez en déposer une autre."],
                ['Rapport' => $this->labelFor($report)],
                highlight: $comment, highlightTitle: 'Commentaire',
                ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-correction',
            );
        } else {
            $this->notifier->toManagers($req, 'Avis d\'un superviseur', [
                "{$who} (superviseur) a " . ($decision === 'approved' ? 'approuvé' : 'émis des réserves sur') . ' la version corrigée du rapport final. À vous de trancher.',
            ], ['Stagiaire' => $req->fullName(), 'Rapport' => $this->labelFor($report)], 'stage-superviseur');
        }

        return $report->fresh();
    }

    public function isValidated(StageReport $r): bool
    {
        return $r->status === 'approved' || $r->status === 'supervisor_approved';
    }

    private function leadDecision(StageReport $r): ?string
    {
        return DB::table('stage_report_reviews')->where('report_id', $r->id)->where('version', $r->current_version)
            ->where('role', 'responsable')->orderByDesc('id')->value('decision');
    }

    private function state(array $p, ?StageReport $report, CarbonImmutable $today): string
    {
        if ($report) {
            return $this->isValidated($report) ? 'validated'
                : (in_array($report->status, ['rejected', 'supervisor_rejected'], true) ? 'to_fix' : 'deposited');
        }
        if ($today->toDateString() < $p['opens']) {
            return 'upcoming';
        }

        return $today->toDateString() > $p['closes'] ? 'missing' : 'open';
    }

    public function labelFor(StageReport $r): string
    {
        if ($r->kind === 'final') {
            return 'Rapport final';
        }
        $s = $r->period_start ? CarbonImmutable::parse($r->period_start->toDateString())->locale('fr') : null;

        return match ($r->frequency) {
            'journalier' => 'Rapport du ' . ($s?->isoFormat('dddd D MMMM') ?? $r->period_key),
            'mensuel'    => 'Rapport de ' . ($s?->isoFormat('MMMM YYYY') ?? $r->period_key),
            default      => 'Rapport de la semaine du ' . ($s?->startOfWeek(CarbonImmutable::MONDAY)->isoFormat('D MMMM') ?? $r->period_key),
        };
    }

    /** Rappel à faire aujourd'hui pour cette période (rapport non déposé), ou null. */
    private function reminderDue(array $p, CarbonImmutable $today): ?string
    {
        $d = $today->toDateString();
        $closes = CarbonImmutable::parse($p['closes']);

        return match (true) {
            $p['opens'] === $d => 'open',
            $p['closes'] === $d && $p['opens'] < $p['closes'] => 'closing',
            $p['kind'] === 'final' && $closes->subDays(7)->toDateString() === $d && $p['opens'] < $d => 'mid',
            ReportPlan::addWorkingDays($closes, 1)->toDateString() === $d => 'late',
            default => null,
        };
    }

    private function remindIntern(StageRequest $req, array $p, string $type): void
    {
        $final = $p['kind'] === 'final';
        [$title, $text] = match ($type) {
            'closing' => ['Dernier jour pour votre rapport', 'Vous n\'avez pas encore déposé ce rapport : aujourd\'hui est le dernier jour du délai.'],
            'mid'     => ['Rapport final : plus que 7 jours', 'Votre stage se termine dans 7 jours. Déposez votre rapport final en PDF depuis votre espace stagiaire.'],
            'late'    => ['Rapport en retard', 'Le délai de ce rapport est passé et il n\'a pas été déposé. Déposez-le dès que possible : il sera accepté, mais marqué en retard.'],
            default   => $final
                ? ['Rapport final à déposer', 'Le dépôt de votre rapport final est ouvert. Déposez-le en PDF depuis votre espace stagiaire, assez tôt pour qu\'il soit validé avant la fin de votre stage.']
                : ['Rapport à déposer aujourd\'hui', 'C\'est aujourd\'hui le jour du dépôt de votre rapport. Déposez-le en PDF depuis votre espace stagiaire.'],
        };

        $this->notifier->toDemandeur(
            $req,
            $title,
            [$text],
            ['Rapport' => $p['label'], ($type === 'late' ? 'Délai dépassé le' : 'À déposer avant le') => $this->requests->frDate($p['closes'])],
            ctaLabel: 'Déposer mon rapport', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-rappel',
        );
    }

    private function notice(StageRequest $req, string $key, string $kind): bool
    {
        return DB::table('stage_report_notices')->insertOrIgnore([
            'stage_request_id' => $req->id, 'period_key' => $key, 'kind' => $kind, 'created_at' => now(),
        ]) === 1;
    }

    /** Superviseurs du stagiaire + responsables. */
    private function staffUrl(StageReport $r): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages/rapports?rapport=' . $r->id;
    }


    private function history(StageRequest $req, string $action, ?User $by, ?string $comment): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => $action, 'from_status' => $req->status, 'to_status' => $req->status,
            'actor' => $by ? 'staff' : 'demandeur', 'user_id' => $by?->id, 'comment' => $comment,
        ]);
    }
}

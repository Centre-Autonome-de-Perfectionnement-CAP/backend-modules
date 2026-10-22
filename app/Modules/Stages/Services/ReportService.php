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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public const MAX_KB = 10240;

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

        return [
            'frequency' => $this->settings->get('report_frequency'),
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

    /** Le rapport final est-il validé ? (condition de l'attestation) */
    public function finalValidated(StageRequest $req): bool
    {
        $r = StageReport::where('stage_request_id', $req->id)->where('period_key', ReportPlan::FINAL_KEY)->first();

        return $r !== null && $this->isValidated($r);
    }

    // ════════════════════════════════════════════════════════════════════
    // DÉPÔT (stagiaire)
    // ════════════════════════════════════════════════════════════════════

    public function submit(StageRequest $req, string $periodKey, UploadedFile $file, ?string $note): StageReport
    {
        $today = BusinessHours::today();
        $period = collect($this->plan($req))->firstWhere('key', $periodKey);
        if (!$period) {
            throw new BusinessException('Cette période de rapport n\'existe pas.', 'STAGE_REPORT_PERIOD', 422);
        }
        if ($today->toDateString() < $period['opens']) {
            throw new BusinessException('Le dépôt de ce rapport ouvre le ' . $this->requests->frDate($period['opens']) . '.', 'STAGE_REPORT_NOT_OPEN', 422);
        }
        $late = $today->toDateString() > $period['closes'];

        $stored = null;
        try {
            $report = DB::transaction(function () use ($req, $period, $file, $note, $late, &$stored) {
                $report = StageReport::where('stage_request_id', $req->id)->where('period_key', $period['key'])->lockForUpdate()->first();
                if ($report && $this->isValidated($report)) {
                    throw new BusinessException('Ce rapport est déjà validé : il ne peut plus être modifié.', 'STAGE_REPORT_LOCKED', 409);
                }
                if (!$report) {
                    $report = StageReport::create([
                        'stage_request_id' => $req->id, 'kind' => $period['kind'], 'period_key' => $period['key'],
                        'frequency' => $period['kind'] === 'final' ? null : $this->settings->get('report_frequency'),
                        'period_start' => $period['start'], 'period_end' => $period['end'],
                        'status' => 'submitted', 'late' => $late, 'submitted_at' => now(),
                    ]);
                }
                $version = $report->current_version + 1;
                $stored = $file->storeAs("stages/{$req->reference}/rapports", strtolower($period['key']) . "-v{$version}.pdf", StageRequestService::DISK);
                if (!$stored) {
                    throw new \RuntimeException('Enregistrement du fichier impossible');
                }
                StageReportVersion::create([
                    'report_id' => $report->id, 'version' => $version, 'file_path' => $stored,
                    'original_name' => mb_substr(preg_replace('/[^\pL\pN\s._-]/u', '', $file->getClientOriginalName()) ?: 'rapport.pdf', 0, 200),
                    'size' => (int) $file->getSize(), 'note' => $note, 'late' => $late,
                ]);
                $report->fill(['current_version' => $version, 'status' => 'submitted', 'submitted_at' => now()])->save();
                $this->history($req, 'report_submitted', null, $this->labelFor($report) . " - version {$version}" . ($late ? ' (en retard)' : ''));

                return $report;
            });
        } catch (\Throwable $e) {
            if ($stored) {
                Storage::disk(StageRequestService::DISK)->delete($stored);
            }
            throw $e;
        }

        // Rapports journaliers : récapitulatif quotidien au personnel (pas un message par dépôt).
        if ($report->kind === 'final' || $report->frequency !== 'journalier') {
            $this->notifier->toStaff(
                $this->staffFor($req),
                $report->kind === 'final' ? 'Rapport final déposé' : 'Rapport déposé',
                [$report->current_version > 1 ? 'Le stagiaire a déposé une nouvelle version de son rapport.' : 'Le stagiaire a déposé son rapport.'],
                ['Stagiaire' => $req->fullName(), 'Rapport' => $this->labelFor($report), 'Version' => (string) $report->current_version, 'Dépôt' => $late ? 'En retard' : null],
                "stage-rapport:{$req->reference}",
                $this->staffUrl($report),
                $req->reference,
            );
        }

        return $report->fresh();
    }

    // ════════════════════════════════════════════════════════════════════
    // COMMENTAIRES ET DÉCISIONS
    // ════════════════════════════════════════════════════════════════════

    public function comment(StageReport $report, string $body, ?User $staff): void
    {
        $body = trim(strip_tags($body));
        if ($body === '') {
            throw new BusinessException('Le commentaire est vide.', 'STAGE_REPORT_EMPTY_COMMENT', 422);
        }
        DB::table('stage_report_comments')->insert([
            'report_id' => $report->id, 'version' => $report->current_version,
            'author' => $staff ? 'staff' : 'stagiaire', 'user_id' => $staff?->id, 'body' => $body, 'created_at' => now(),
        ]);

        $req = $report->stage;
        if ($staff) {
            $this->notifier->toDemandeur($req, 'Commentaire sur votre rapport', [
                trim($staff->first_name . ' ' . $staff->last_name) . ' a commenté votre rapport.',
            ], ['Rapport' => $this->labelFor($report)], highlight: $body, highlightTitle: 'Commentaire',
                ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->internUrl(), context: 'stage-rapport-commentaire');
        } else {
            $this->notifier->toStaff($this->staffFor($req), 'Réponse du stagiaire', [
                'Le stagiaire a répondu sur son rapport.',
            ], ['Stagiaire' => $req->fullName(), 'Rapport' => $this->labelFor($report), 'Message' => $body],
                "stage-rapport:{$req->reference}", $this->staffUrl($report), $req->reference);
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

        $before = $this->isValidated($report);
        if (!$lead && $this->leadDecision($report) !== null) {
            throw new BusinessException('Le responsable de division a déjà tranché sur cette version.', 'STAGE_REPORT_LEAD_DECIDED', 409);
        }

        DB::transaction(function () use ($report, $decision, $comment, $by, $role) {
            DB::table('stage_report_reviews')->insert([
                'report_id' => $report->id, 'version' => $report->current_version, 'user_id' => $by->id,
                'role' => $role, 'decision' => $decision, 'comment' => $comment, 'created_at' => now(),
            ]);
            $report->status = $role === 'responsable' ? $decision : 'supervisor_' . $decision;
            $report->save();
            $this->history($report->stage, 'report_' . $decision, $by, $this->labelFor($report) . ($comment ? " : {$comment}" : ''));
        });

        $req = $report->stage;
        $who = trim($by->first_name . ' ' . $by->last_name);
        $this->notifier->toDemandeur(
            $req,
            $decision === 'approved' ? 'Rapport validé' : 'Rapport à corriger',
            [$decision === 'approved'
                ? "{$who} a validé votre rapport."
                : "{$who} vous demande de corriger votre rapport et d'en déposer une nouvelle version."],
            ['Rapport' => $this->labelFor($report)],
            highlight: $comment, highlightTitle: 'Commentaire',
            ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->internUrl(), context: 'stage-rapport-decision',
        );
        if (!$lead) {
            $this->notifier->toManagers($req, 'Action d\'un superviseur', [
                "{$who} (superviseur) a " . ($decision === 'approved' ? 'validé' : 'demandé la correction d\'') . 'un rapport. Vous pouvez confirmer ou revenir sur cette décision.',
            ], ['Stagiaire' => $req->fullName(), 'Rapport' => $this->labelFor($report)], 'stage-superviseur');
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
        $comments = DB::table('stage_report_comments as c')->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->where('c.report_id', $report->id)->orderBy('c.id')
            ->get(['c.id', 'c.version', 'c.author', 'c.body', 'c.created_at', 'u.first_name', 'u.last_name']);
        $reviews = DB::table('stage_report_reviews as r')->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.report_id', $report->id)->orderBy('r.id')
            ->get(['r.id', 'r.version', 'r.role', 'r.decision', 'r.comment', 'r.created_at', 'u.first_name', 'u.last_name']);

        return $this->summary($report) + [
            'stage' => $forStaff ? [
                'id' => $report->stage->id, 'reference' => $report->stage->reference,
                'nom_complet' => trim($report->stage->civilite . ' ' . $report->stage->fullName()),
            ] : null,
            'versions' => $report->versions()->get()->map(fn ($v) => [
                'version' => $v->version, 'name' => $v->original_name, 'size' => $v->size, 'note' => $v->note,
                'late' => $v->late, 'at' => $v->created_at?->toIso8601String(),
            ])->values(),
            'comments' => $comments->map(fn ($c) => [
                'id' => $c->id, 'version' => $c->version, 'author' => $c->author,
                'name' => $c->author === 'staff' ? trim($c->first_name . ' ' . $c->last_name) : 'Stagiaire',
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
            'locked'          => $this->isValidated($r),
            'current_version' => $r->current_version,
            'late'            => $r->late,
            'submitted_at'    => $r->submitted_at?->toIso8601String(),
        ];
    }

    /** Liste pour le personnel. */
    public function staffList(string $filter, ?User $mineOf): Collection
    {
        $q = StageReport::with('stage')->orderByDesc('submitted_at')->limit(300);
        if ($filter === 'to_review') {
            $q->whereIn('status', ['submitted', 'supervisor_approved', 'supervisor_rejected']);
        } elseif ($filter !== 'all') {
            $q->where('status', $filter);
        }
        if ($mineOf) {
            $q->whereIn('stage_request_id', DB::table('stage_supervisions')->where('user_id', $mineOf->id)->select('stage_request_id'));
        }

        return $q->get()->map(fn ($r) => $this->summary($r) + [
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

    /** @return array{opened: int, closing: int, digest: int} */
    public function tick(?CarbonImmutable $today = null, bool $reminders = true, bool $digest = true): array
    {
        $today ??= BusinessHours::today();
        $stats = ['opened' => 0, 'closing' => 0, 'digest' => 0];

        $interns = !$reminders ? collect() : StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->where('date_fin', '>=', $today->subDays(10)->toDateString())->get();

        foreach ($interns as $req) {
            try {
                $reports = StageReport::where('stage_request_id', $req->id)->pluck('period_key')->all();
                foreach ($this->plan($req) as $p) {
                    if (in_array($p['key'], $reports, true)) {
                        continue;
                    }
                    if ($p['opens'] === $today->toDateString() && $this->notice($req, $p['key'], 'open')) {
                        $this->remindIntern($req, $p, false);
                        $stats['opened']++;
                    } elseif ($p['closes'] === $today->toDateString() && $p['opens'] < $p['closes'] && $this->notice($req, $p['key'], 'closing')) {
                        $this->remindIntern($req, $p, true);
                        $stats['closing']++;
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
            $this->notifier->toManagersGeneric('Rapports journaliers du jour', [
                $daily->count() . ' rapport(s) journalier(s) déposé(s) aujourd\'hui.',
            ], $daily->groupBy('stage_request_id')->mapWithKeys(fn ($g) => [$g->first()->stage->fullName() => $g->count() . ' rapport(s)'])->all(),
                'stage-rapports-journaliers', rtrim((string) config('app.url'), '/') . '/services/stages/rapports');
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

        return ReportPlan::periods(
            CarbonImmutable::parse($start->toDateString()),
            CarbonImmutable::parse($req->date_fin->toDateString()),
            (string) $this->settings->get('report_frequency'),
            (int) $this->settings->get('report_grace_days'),
            (int) $this->settings->get('final_report_open_days'),
        );
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

    private function remindIntern(StageRequest $req, array $p, bool $closing): void
    {
        $this->notifier->toDemandeur(
            $req,
            $closing ? 'Dernier jour pour votre rapport' : ($p['kind'] === 'final' ? 'Rapport final à déposer' : 'Rapport à déposer'),
            [$closing
                ? 'Vous n\'avez pas encore déposé ce rapport : aujourd\'hui est le dernier jour du délai.'
                : 'Le dépôt de votre rapport est ouvert. Déposez-le en PDF depuis votre espace stagiaire.'],
            ['Rapport' => $p['label'], 'À déposer avant le' => $this->requests->frDate($p['closes'])],
            ctaLabel: 'Déposer mon rapport', ctaUrl: $this->internUrl(), context: 'stage-rapport-rappel',
        );
    }

    private function notice(StageRequest $req, string $key, string $kind): bool
    {
        return DB::table('stage_report_notices')->insertOrIgnore([
            'stage_request_id' => $req->id, 'period_key' => $key, 'kind' => $kind, 'created_at' => now(),
        ]) === 1;
    }

    /** Superviseurs du stagiaire + responsables. */
    private function staffFor(StageRequest $req): Collection
    {
        $sup = DB::table('stage_supervisions as s')->join('users as u', 'u.id', '=', 's.user_id')
            ->where('s.stage_request_id', $req->id)->whereNull('u.deleted_at')
            ->get(['u.id', 'u.email', 'u.phone', 'u.first_name', 'u.last_name']);

        return $sup->concat(StageAccess::managers());
    }

    private function staffUrl(StageReport $r): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages/rapports?rapport=' . $r->id;
    }

    private function internUrl(): string
    {
        return rtrim((string) config('app.frontend_url', config('app.url')), '/') . '/espace-stagiaire/rapports';
    }

    private function history(StageRequest $req, string $action, ?User $by, ?string $comment): void
    {
        StageHistory::create([
            'stage_request_id' => $req->id, 'action' => $action, 'from_status' => $req->status, 'to_status' => $req->status,
            'actor' => $by ? 'staff' : 'demandeur', 'user_id' => $by?->id, 'comment' => $comment,
        ]);
    }
}

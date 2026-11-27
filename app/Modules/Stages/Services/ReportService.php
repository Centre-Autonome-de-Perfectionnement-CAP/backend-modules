<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageAttestation;
use App\Modules\Stages\Models\StageHistory;
use App\Modules\Stages\Models\StageReport;
use App\Modules\Stages\Models\StageReportVersion;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\ReportPlan;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
 *  - superviseurs et responsable commentent et valident ; chaque version se
 *    décide une fois par rôle, sous verrou ; le responsable a le dernier mot
 *    et peut renvoyer un rapport validé à corriger tant que l'attestation
 *    n'est pas validée ; un rapport en correction accepte une nouvelle demande
 *    de correction mais pas de validation avant une nouvelle version ;
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

    /** Commentaires : un message par rafale, 10 minutes après le premier. */
    private const COMMENT_BURST_MINUTES = 10;

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly StageSettingsService $settings,
        private readonly StageRequestService $requests,
        private readonly BinomeService $binomes,
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
                'state'  => $this->state($req, $p, $report, $today),
                'report' => $report ? $this->summary($report) : null,
            ];
        }
        // Rapports déposés hors calendrier actuel (changement de fréquence) : gardés.
        foreach ($reports as $key => $report) {
            if (!collect($plan)->contains('key', $key)) {
                $items[] = [
                    'key' => $key, 'kind' => $report->kind, 'label' => $this->labelFor($report),
                    'start' => $report->period_start?->toDateString(), 'end' => $report->period_end?->toDateString(),
                    'opens' => null, 'closes' => null,
                    'state' => $this->isValidated($report) ? 'validated' : (in_array($report->status, ['rejected', 'supervisor_rejected'], true) ? 'to_fix' : 'deposited'),
                    'report' => $this->summary($report),
                ];
            }
        }

        $final = collect($items)->firstWhere('key', ReportPlan::FINAL_KEY);

        $partner = $this->partnerFor($req);

        return [
            'frequency' => $this->settings->get('report_frequency'),
            'binome'    => $partner ? ['name' => $partner->fullName(), 'final_commun' => $this->finalShared($req, $partner)] : null,
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

    /** Rapport final commun au binôme (un même mémoire, déposé une fois pour les deux) ? */
    public function finalShared(StageRequest $req, ?StageRequest $partner = null): bool
    {
        return ($partner ?? $this->partnerFor($req)) !== null && $this->binomes->finalMode($req->team_id) === BinomeService::FINAL_COMMUN;
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
     * Rapport final : en binôme seulement s'il est commun (et alors toujours).
     */
    private function pairFor(StageRequest $req, ?StageReport $report, array $period, bool $forPair): array
    {
        $none = [null, null, null];
        $final = $period['kind'] === 'final';
        if (!$this->pairingReady()) {
            return $none;
        }
        if ($report && $report->pair_report_id) {
            $twin = $this->twinOf($report, true);

            return $twin ? [$twin, $twin->stage, collect($this->plan($twin->stage))->firstWhere('key', $period['key']) ?? $period] : $none;
        }
        if ($final) {
            $forPair = $this->finalShared($req);
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
            throw new BusinessException($final
                ? $partner->fullName() . ' a déjà déposé son propre rapport de fin de stage : le rapport commun n\'est plus possible. Contactez le responsable de division.'
                : $partner->fullName() . ' a déjà déposé son propre rapport pour cette période. Décochez « Déposer pour mon binôme » pour déposer le vôtre.',
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
            // Rapport déjà déposé sur une période qui a disparu du plan (changement
            // de fréquence, prolongation, etc.) : on autorise quand même une nouvelle
            // version en reconstruisant la période depuis le rapport existant.
            $existing = StageReport::where('stage_request_id', $req->id)->where('period_key', $periodKey)->first();
            if (!$existing) {
                throw new BusinessException('Cette période de rapport n\'existe pas.', 'STAGE_REPORT_PERIOD', 422);
            }
            $period = [
                'key'    => $existing->period_key,
                'kind'   => $existing->kind,
                'label'  => $this->labelFor($existing),
                'start'  => $existing->period_start?->toDateString(),
                'end'    => $existing->period_end?->toDateString(),
                'opens'  => $existing->period_start?->toDateString() ?? BusinessHours::today()->toDateString(),
                'closes' => $existing->period_end?->toDateString() ?? BusinessHours::today()->toDateString(),
            ];
        }
        // Un rapport déjà déposé reste ouvert aux nouvelles versions (correction demandée).
        if ($today->toDateString() < $period['opens']
            && !StageReport::where('stage_request_id', $req->id)->where('period_key', $period['key'])->exists()) {
            throw new BusinessException('Le dépôt de ce rapport ouvre le ' . $this->requests->frDate($period['opens']) . '.', 'STAGE_REPORT_NOT_OPEN', 422);
        }
        $late = $this->isLate($req, $period, $today);

        $stored = null;
        $postDefense = false;
        $twin = null;
        $requester = null;
        try {
            $report = DB::transaction(function () use ($req, $period, $file, $note, $forPair, $today, &$late, &$stored, &$postDefense, &$twin, &$requester) {
                $report = StageReport::where('stage_request_id', $req->id)->where('period_key', $period['key'])->lockForUpdate()->first();
                // Correction demandée : qui l'a demandée (prévenu avec les superviseurs titulaires).
                if ($report && in_array($report->status, ['rejected', 'supervisor_rejected'], true)) {
                    $requester = DB::table('stage_report_reviews')->where('report_id', $report->id)->where('version', $report->current_version)
                        ->where('decision', 'rejected')->orderByDesc('id')->value('user_id') ?? 0;
                }
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
                    // Rapport final commun validé : la version corrigée après soutenance vaut aussi pour le binôme.
                    $twinPost = $postDefense && $this->isValidated($twin);
                    $this->addVersion($twin, $req, $stored, $name, (int) $file->getSize(), $note, $twinPost ? false : $twinLate, $twinPost);
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
        if ($requester !== null) {
            // Nouvelle version après une demande de correction : les superviseurs
            // titulaires et la personne qui l'a demandée, un seul message chacun.
            $to = collect(array_filter([$req, $partner]))->flatMap(fn ($r) => $this->notifier->supervisorsOf($r))
                ->concat(DB::table('users')->where('id', $requester)->whereNull('deleted_at')->get(['id', 'email', 'phone', 'first_name', 'last_name']))
                ->unique('id')->values();
            $this->notifier->toStaff($to, 'Rapport corrigé', [
                $req->fullName() . ' a déposé la version corrigée de son rapport' . ($partner ? ' (pour lui et son binôme, ' . $partner->fullName() . ')' : '') . '.',
            ], ['Stagiaire' => $req->fullName(), 'Binôme' => $partner?->fullName(), 'Rapport' => $this->labelFor($report),
                'Version' => (string) $report->current_version], 'stage-rapport', $this->staffUrl($report), $req->reference);
        } elseif ($report->kind === 'final' || $report->frequency !== 'journalier') {
            // Rapports journaliers : récapitulatif quotidien au personnel (pas un message par dépôt).
            $this->notifier->toFollowers(
                $partner ? [$req, $partner] : $req,
                $postDefense ? 'Rapport de fin de stage corrigé après soutenance' : ($report->kind === 'final' ? 'Rapport de fin de stage déposé' : 'Rapport déposé'),
                [$postDefense
                    ? 'Le stagiaire a déposé la version corrigée de son rapport de fin de stage, après la soutenance. Sa validation précédente reste acquise.'
                    : ($partner
                        ? 'Le stagiaire a déposé le rapport pour lui et son binôme (' . $partner->fullName() . ').'
                        : ($report->current_version > 1 ? 'Le stagiaire a déposé une nouvelle version de son rapport.' : 'Le stagiaire a déposé son rapport.'))],
                ['Stagiaire' => $req->fullName(), 'Binôme' => $partner?->fullName(), 'Rapport' => $this->labelFor($report),
                    'Version' => (string) $report->current_version, 'Dépôt' => $late ? 'En retard' : null],
                'stage-rapport',
                $this->staffUrl($report),
            );
        }
        $this->announcePeriod($report, array_filter([$req->id, $partner?->id]));
        if ($partner) {
            $this->notifier->toDemandeur($partner, 'Rapport déposé pour votre binôme', [
                $req->fullName() . ' a déposé le rapport pour vous deux. Vous le retrouvez dans vos rapports ; les commentaires et la décision du superviseur valent pour vous deux.',
            ], ['Rapport' => $this->labelFor($twin), 'Version' => (string) $twin->current_version],
                ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-binome');
        }

        return $report->fresh();
    }

    /**
     * Premier rapport déposé pour une période (journalière, hebdomadaire ou
     * mensuelle) : tous les stagiaires et le personnel sont invités, une
     * seule fois, à consulter les rapports et à les commenter.
     */
    private function announcePeriod(StageReport $report, array $except): void
    {
        if ($report->kind === 'final' || $report->current_version !== 1 || !$this->notifier->digestReady()) {
            return;
        }
        try {
            if (!DB::table('stage_report_broadcasts')->insertOrIgnore(['period_key' => $report->period_key, 'created_at' => now()])) {
                return; // période déjà annoncée
            }
            // « Rapport de la semaine du 2 novembre » → « de la semaine du 2 novembre »
            $period = preg_replace('/^Rapport /u', '', $this->labelFor($report));
            $this->notifier->toEveryone('Période de consultation des rapports', [
                "La période de consultation des rapports {$period} est ouverte : les premiers rapports sont déposés.",
                'Consultez les rapports de chacun et commentez-les sur la plateforme. Faites diligence et restez attentifs aux échanges.',
            ], $this->notifier->spaceUrl('/rapports/tous'), rtrim((string) config('app.url'), '/') . '/services/stages/rapports',
                'stage-rapports-consultation', $except);
        } catch (\Throwable $e) {
            Log::error('[Stages] Annonce de la période de rapports impossible', ['period' => $report->period_key, 'error' => $e->getMessage()]);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // COMMENTAIRES ET DÉCISIONS
    // ════════════════════════════════════════════════════════════════════

    /**
     * Commentaire du personnel, du stagiaire ou d'un autre stagiaire (les
     * rapports sont ouverts à tous). Rapport de binôme : le message figure
     * sur les deux rapports. Les concernés sont prévenus par rafale de
     * 10 minutes (voir flushComments()).
     */
    public function comment(StageReport $report, string $body, ?User $staff, ?StageRequest $intern = null): void
    {
        $body = trim(strip_tags($body));
        if ($body === '') {
            throw new BusinessException('Le commentaire est vide.', 'STAGE_REPORT_EMPTY_COMMENT', 422);
        }
        $author = $staff ? null : ($intern ?? $report->stage);
        if ($author && $author->id !== $report->stage_request_id && !$this->pairingReady()) {
            throw new BusinessException('Les commentaires sur les rapports des autres stagiaires ne sont pas encore disponibles.', 'STAGE_REPORT_COMMENT_UNAVAILABLE', 409);
        }
        $twin = $this->twinOf($report);
        $id = DB::transaction(function () use ($report, $twin, $body, $staff, $author) {
            $ids = [];
            foreach (array_filter([$report, $twin]) as $r) {
                $ids[] = DB::table('stage_report_comments')->insertGetId([
                    'report_id' => $r->id, 'version' => $r->current_version,
                    'author' => $staff ? 'staff' : 'stagiaire', 'user_id' => $staff?->id, 'body' => $body, 'created_at' => now(),
                ] + ($author && $this->pairingReady() ? ['stage_request_id' => $author->id] : []));
            }

            return $ids[0];
        });

        if ($this->notifier->digestReady()) {
            try {
                DB::table('stage_digest_items')->insert(['kind' => 'report_comment', 'report_id' => $report->id, 'comment_id' => $id, 'created_at' => now()]);

                return;
            } catch (\Throwable $e) {
                Log::warning('[Stages] Commentaire non mis en attente : envoi direct', ['error' => $e->getMessage()]);
            }
        }
        $this->notifyComments($report, $this->commentRows([$id]));
    }

    /** Supprime un commentaire : son auteur, ou le personnel. */
    public function deleteComment(StageReport $report, int $commentId, ?User $staff, ?StageRequest $intern = null): void
    {
        $c = DB::table('stage_report_comments')->where('report_id', $report->id)->where('id', $commentId)->first();
        if (!$c) {
            throw new BusinessException('Ce commentaire n\'existe plus.', 'STAGE_REPORT_COMMENT_NOT_FOUND', 404);
        }
        $ownerOf = (int) ($c->stage_request_id ?? 0) ?: $report->stage_request_id;
        if (!$staff && !($c->author === 'stagiaire' && $intern && $ownerOf === $intern->id)) {
            throw new BusinessException('Vous ne pouvez supprimer que vos propres commentaires.', 'STAGE_REPORT_COMMENT_FORBIDDEN', 403);
        }
        $twin = $this->twinOf($report);
        DB::transaction(function () use ($c, $twin) {
            DB::table('stage_report_comments')->where('id', $c->id)->delete();
            // Copie sur le rapport du binôme
            if ($twin) {
                DB::table('stage_report_comments')->where('report_id', $twin->id)->where('author', $c->author)
                    ->where('user_id', $c->user_id)->where('body', $c->body)->where('created_at', $c->created_at)
                    ->when(isset($c->stage_request_id), fn ($q) => $q->where('stage_request_id', $c->stage_request_id))
                    ->limit(1)->delete();
            }
            if ($this->notifier->digestReady()) {
                DB::table('stage_digest_items')->where('kind', 'report_comment')->where('comment_id', $c->id)->delete();
            }
        });
    }

    /**
     * Commentaires en attente : un message par rapport, quand la rafale a
     * commencé il y a 10 minutes au moins (tâche toutes les 5 minutes).
     */
    public function flushComments(?CarbonImmutable $now = null): int
    {
        if (!$this->notifier->digestReady()) {
            return 0;
        }
        $now ??= CarbonImmutable::now();
        $items = DB::table('stage_digest_items')->where('kind', 'report_comment')->orderBy('id')->get();
        $sent = 0;
        foreach ($items->groupBy('report_id') as $reportId => $group) {
            if (CarbonImmutable::parse($group->first()->created_at)->gt($now->subMinutes(self::COMMENT_BURST_MINUTES))) {
                continue; // rafale en cours
            }
            try {
                $report = StageReport::with('stage')->find($reportId);
                $rows = $this->commentRows($group->pluck('comment_id')->all());
                if ($report && $rows->isNotEmpty()) {
                    $this->notifyComments($report, $rows);
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::error('[Stages] Commentaires de rapport non signalés', ['report_id' => $reportId, 'error' => $e->getMessage()]);
            }
            DB::table('stage_digest_items')->whereIn('id', $group->pluck('id'))->delete();
        }

        return $sent;
    }

    /** Commentaires (encore présents) avec le nom de leur auteur. */
    private function commentRows(array $ids): Collection
    {
        $rows = DB::table('stage_report_comments as c')->leftJoin('users as u', 'u.id', '=', 'c.user_id')
            ->whereIn('c.id', $ids)->orderBy('c.id')->get(['c.*', 'u.first_name', 'u.last_name']);
        $names = StageRequest::whereIn('id', $rows->pluck('stage_request_id')->filter()->unique())->get()
            ->mapWithKeys(fn ($r) => [$r->id => $r->fullName()]);

        return $rows->map(function ($c) use ($names) {
            $c->name = $c->author === 'staff' ? trim($c->first_name . ' ' . $c->last_name)
                : ($names[$c->stage_request_id ?? 0] ?? null);

            return $c;
        });
    }

    /**
     * Prévient les concernés : le ou les stagiaires du rapport (sauf pour
     * leurs propres messages) et leurs superviseurs (messages des stagiaires).
     */
    private function notifyComments(StageReport $report, Collection $rows): void
    {
        $twin = $this->twinOf($report);
        $owners = array_values(array_filter([$report->stage, $twin?->stage]));
        $label = $this->labelFor($report);
        $text = fn (Collection $list) => $list->take(5)->map(fn ($c) => ($c->name ?: 'Stagiaire') . ' : ' . mb_strimwidth($c->body, 0, 300, '…'))->implode("\n\n")
            . ($list->count() > 5 ? "\n\n… et " . ($list->count() - 5) . ' autre(s)' : '');

        foreach ($owners as $owner) {
            $others = $rows->reject(fn ($c) => $c->author === 'stagiaire' && (int) ($c->stage_request_id ?? $report->stage_request_id) === $owner->id);
            if ($others->isEmpty()) {
                continue;
            }
            $n = $others->count();
            $this->notifier->toDemandeur($owner, $n > 1 ? 'Nouveaux commentaires sur votre rapport' : 'Commentaire sur votre rapport', [
                ($n > 1 ? "{$n} nouveaux commentaires" : 'Un nouveau commentaire') . ' sur ' . ($twin ? 'le rapport de votre binôme' : 'votre rapport') . " « {$label} ».",
            ], [], highlight: $text($others), highlightTitle: $n > 1 ? 'Commentaires' : 'Commentaire',
                ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-commentaire');
        }

        // Autres stagiaires qui ont déjà commenté ce rapport : prévenus des nouvelles réponses (sauf les leurs).
        $ownerIds = collect($owners)->pluck('id')->all();
        $participants = StageRequest::whereIn('id', DB::table('stage_report_comments')->whereIn('report_id', array_filter([$report->id, $twin?->id]))
            ->where('author', 'stagiaire')->whereNotNull('stage_request_id')->whereNotIn('id', $rows->pluck('id'))
            ->distinct()->pluck('stage_request_id'))->whereNotIn('id', $ownerIds)->get();
        foreach ($participants as $p) {
            $others = $rows->reject(fn ($c) => $c->author === 'stagiaire' && (int) $c->stage_request_id === $p->id);
            if ($others->isEmpty()) {
                continue;
            }
            $n = $others->count();
            $this->notifier->toDemandeur($p, $n > 1 ? 'Nouvelles réponses sur un rapport' : 'Nouvelle réponse sur un rapport', [
                ($n > 1 ? "{$n} nouveaux commentaires" : 'Un nouveau commentaire') . ' sur « ' . $label . ' » de ' . collect($owners)->map->fullName()->implode(' et ') . ', que vous avez commenté.',
            ], [], highlight: $text($others), highlightTitle: $n > 1 ? 'Commentaires' : 'Commentaire',
                ctaLabel: 'Ouvrir les rapports', ctaUrl: $this->notifier->spaceUrl('/rapports/tous'), context: 'stage-rapport-commentaire');
        }

        $fromInterns = $rows->where('author', 'stagiaire');
        if ($fromInterns->isNotEmpty() && $owners) {
            $n = $fromInterns->count();
            $this->notifier->toFollowers($owners, $n > 1 ? 'Commentaires de stagiaires sur un rapport' : 'Commentaire d\'un stagiaire sur un rapport', [
                ($n > 1 ? "{$n} commentaires" : 'Un commentaire') . " sur « {$label} ».",
                $text($fromInterns),
            ], ['Stagiaire' => collect($owners)->map->fullName()->implode(' et ')], 'stage-rapport', $this->staffUrl($report));
        }
    }

    /**
     * Décision sur la version actuelle d'un rapport (superviseur ou responsable).
     *
     * Tout se joue sous verrou : l'état est relu une fois le rapport (et son
     * jumeau) verrouillé, pour qu'une même version ne soit jamais validée ou
     * renvoyée deux fois (double clic, deux décideurs en même temps).
     * Règles : voir decisionBlock().
     */
    public function decide(StageReport $report, string $decision, ?string $comment, User $by): StageReport
    {
        if ($decision === 'rejected' && !$comment) {
            throw new BusinessException('Indiquez ce qui doit être corrigé.', 'STAGE_REPORT_REJECT_COMMENT', 422);
        }
        $lead = StageAccess::isLead($by);
        $role = $lead ? 'responsable' : 'superviseur';

        [$report, $both, $correction, $before] = DB::transaction(function () use ($report, $decision, $comment, $by, $lead, $role) {
            $report = $this->lockPair($report);
            if ($report->current_version < 1) {
                throw new BusinessException('Aucune version à examiner.', 'STAGE_REPORT_EMPTY', 409);
            }
            $correction = $report->correction_status === 'pending';
            $before = $this->isValidated($report);
            $this->assertDecidable($report, $decision, $lead, $by, $correction);

            // Rapport de binôme : la décision vaut pour les deux rapports (version corrigée : si le jumeau l'attend aussi).
            $twin = $this->twinOf($report);
            $both = $correction
                ? array_values(array_filter([$report, $twin?->correction_status === 'pending' ? $twin : null]))
                : array_values(array_filter([$report, $twin]));
            foreach ($both as $r) {
                DB::table('stage_report_reviews')->insert([
                    'report_id' => $r->id, 'version' => $r->current_version, 'user_id' => $by->id,
                    'role' => $role, 'decision' => $decision, 'comment' => $comment, 'created_at' => now(),
                ]);
                if ($correction) {
                    // Le superviseur donne son avis ; le responsable tranche.
                    if ($lead) {
                        $r->correction_status = $decision;
                        if ($decision === 'approved') {
                            $r->official_version = $r->current_version;
                        }
                        $r->save();
                    }
                    $this->history($r->stage, 'report_correction_' . $decision, $by,
                        $this->labelFor($r) . ' - version corrigée après soutenance' . ($comment ? " : {$comment}" : ''));
                } else {
                    $r->status = $lead ? $decision : 'supervisor_' . $decision;
                    if ($this->isValidated($r)) {
                        $r->official_version = $r->current_version;
                    }
                    $r->save();
                    $this->history($r->stage, 'report_' . $decision, $by, $this->labelFor($r) . ($comment ? " : {$comment}" : ''));
                }
            }

            return [$report, $both, $correction, $before];
        });
        foreach ($both as $r) {
            $this->pruneOldVersions($r);
        }
        $this->notifyDecision($report, $both, $decision, $comment, $by, $lead, $correction, $before);

        return $report->fresh();
    }

    /** Verrouille le rapport et son jumeau (toujours dans le même ordre : pas d'interblocage), relus à jour. */
    private function lockPair(StageReport $report): StageReport
    {
        $pairId = $this->pairingReady() ? StageReport::whereKey($report->id)->value('pair_report_id') : null;
        $locked = StageReport::whereIn('id', array_filter([$report->id, $pairId]))->orderBy('id')->lockForUpdate()->get();

        return $locked->firstWhere('id', $report->id) ?? throw (new ModelNotFoundException())->setModel(StageReport::class, [$report->id]);
    }

    /**
     * Pourquoi cette décision est impossible maintenant, ou null si elle est permise.
     *  - version corrigée après soutenance : un avis par superviseur, le responsable tranche une fois ;
     *  - un rapport en correction (renvoyé au stagiaire) accepte une nouvelle demande de correction,
     *    jamais une validation : il faut d'abord une nouvelle version ;
     *  - superviseur : seulement tant que la version n'a pas été examinée (ou pour redemander une
     *    correction), et jamais après le responsable ;
     *  - responsable (dernier mot) : une validation par version ; il peut ensuite renvoyer le rapport
     *    à corriger tant que l'attestation n'est pas validée.
     *
     * @return array{0: string, 1: string, 2: int}|null [message, code, statut HTTP]
     */
    private function decisionBlock(StageReport $r, string $decision, bool $lead, User $by, bool $correction): ?array
    {
        if ($correction) {
            $reviews = DB::table('stage_report_reviews')->where('report_id', $r->id)->where('version', $r->current_version);
            if (!$lead && (clone $reviews)->where('role', 'responsable')->exists()) {
                return ['Le responsable de division a déjà tranché sur cette version.', 'STAGE_REPORT_LEAD_DECIDED', 409];
            }
            if (!$lead && (clone $reviews)->where('user_id', $by->id)->exists()) {
                return ['Vous avez déjà donné votre avis sur cette version.', 'STAGE_REPORT_ALREADY_DECIDED', 409];
            }

            return null;
        }
        if ($r->correction_status !== null) {
            return ['La version corrigée après soutenance a déjà été jugée : plus de décision possible sur ce rapport.', 'STAGE_REPORT_LOCKED', 409];
        }

        $inCorrection = in_array($r->status, ['rejected', 'supervisor_rejected'], true);
        if ($decision === 'approved' && $inCorrection) {
            return ['Ce rapport est en correction : le stagiaire doit en déposer une nouvelle version avant toute validation.', 'STAGE_REPORT_IN_CORRECTION', 409];
        }

        $leadDecision = $this->leadDecision($r);
        if (!$lead) {
            if ($leadDecision !== null) {
                return ['Le responsable de division a déjà tranché sur cette version.', 'STAGE_REPORT_LEAD_DECIDED', 409];
            }

            return $r->status === 'submitted' || ($decision === 'rejected' && $inCorrection) ? null
                : ['Cette version a déjà été examinée par un superviseur. Le responsable de division a le dernier mot.', 'STAGE_REPORT_ALREADY_DECIDED', 409];
        }
        if ($leadDecision === null || $leadDecision === 'rejected') {
            return null;
        }
        // Le responsable a validé : il ne peut pas valider une seconde fois, ni renvoyer le rapport une fois l'attestation validée.
        if ($decision === 'approved') {
            return ['Ce rapport est déjà validé.', 'STAGE_REPORT_ALREADY_DECIDED', 409];
        }
        if ($this->attestationValidated($r)) {
            return ['Votre validation ne peut plus être retirée : l\'attestation est validée.', 'STAGE_REPORT_LOCKED', 409];
        }

        return null;
    }

    private function assertDecidable(StageReport $r, string $decision, bool $lead, User $by, bool $correction): void
    {
        if ($block = $this->decisionBlock($r, $decision, $lead, $by, $correction)) {
            throw new BusinessException($block[0], $block[1], $block[2]);
        }
    }

    /** Rapport final : l'attestation du stagiaire (ou de son binôme, si le rapport est commun) est-elle validée ? */
    private function attestationValidated(StageReport $r): bool
    {
        if ($r->kind !== 'final') {
            return false;
        }
        $ids = array_filter([$r->stage_request_id, $this->twinOf($r)?->stage_request_id]);

        return StageAttestation::whereIn('stage_request_id', $ids)->whereIn('status', ['a_imprimer', 'imprimee', 'remise'])->exists();
    }

    /**
     * Ce que ce décideur peut faire maintenant sur le rapport (pour l'écran).
     *
     * @return array{approve: bool, reject: bool, reverse: bool}
     */
    private function decisionOptions(StageReport $r, User $by): array
    {
        $lead = StageAccess::isLead($by);
        $correction = $r->correction_status === 'pending';
        $can = fn (string $d) => $r->current_version >= 1 && $this->decisionBlock($r, $d, $lead, $by, $correction) === null;

        return [
            'approve' => $can('approved'),
            'reject'  => $can('rejected'),
            // Retirer sa validation (renvoyer à corriger) : l'écran adapte son intitulé.
            'reverse' => $lead && !$correction && $this->leadDecision($r) === 'approved',
        ];
    }

    /** Prévient le stagiaire (et, selon le cas, le superviseur ou les responsables) après une décision. */
    private function notifyDecision(StageReport $report, array $both, string $decision, ?string $comment, User $by, bool $lead, bool $correction, bool $before): void
    {
        $req = $report->stage;
        $who = trim($by->first_name . ' ' . $by->last_name);

        if ($correction) {
            if ($lead) {
                foreach ($both as $r) {
                    $this->notifier->toDemandeur(
                        $r->stage,
                        $decision === 'approved' ? 'Version corrigée validée' : 'Version corrigée non retenue',
                        [$decision === 'approved'
                            ? "{$who} a validé la version corrigée de " . (count($both) > 1 ? 'votre rapport de fin de stage commun' : 'votre rapport de fin de stage') . ". C'est désormais la version officielle."
                            : "{$who} n'a pas retenu la version corrigée de " . (count($both) > 1 ? 'votre rapport de fin de stage commun' : 'votre rapport de fin de stage') . '. La version validée précédente reste valable ; vous pouvez en déposer une autre.'],
                        ['Rapport' => $this->labelFor($r)],
                        highlight: $comment, highlightTitle: 'Commentaire',
                        ctaLabel: 'Ouvrir mes rapports', ctaUrl: $this->notifier->spaceUrl('/rapports'), context: 'stage-rapport-correction',
                    );
                }
            } else {
                $this->notifier->toManagers($req, 'Avis d\'un superviseur', [
                    "{$who} (superviseur) a " . ($decision === 'approved' ? 'approuvé' : 'émis des réserves sur') . ' la version corrigée du rapport de fin de stage. À vous de trancher.',
                ], ['Stagiaire' => $req->fullName(), 'Rapport' => $this->labelFor($report)], 'stage-superviseur');
            }

            return;
        }

        $twin = $both[1] ?? null;
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
            $this->notifier->supervisorAction($by, ($decision === 'approved' ? 'a validé' : 'a demandé la correction du')
                . ' « ' . $this->labelFor($report) . ' » de ' . $req->fullName() . ($twin ? ' et ' . $twin->stage->fullName() : ''), $req);
        }
        if ($report->kind === 'final' && !$before && $this->isValidated($report->fresh())) {
            Log::info('[Stages] Rapport final validé', ['reference' => $req->reference]);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE
    // ════════════════════════════════════════════════════════════════════

    /**
     * Détail d'un rapport. $viewer : stagiaire qui consulte (le sien, celui
     * de son binôme ou celui d'un autre : les rapports sont ouverts à tous).
     */
    public function detail(StageReport $report, bool $forStaff, ?StageRequest $viewer = null, ?User $by = null): array
    {
        $viewerId = $forStaff ? null : ($viewer?->id ?? $report->stage_request_id);
        $twin = $this->twinOf($report);
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
            // Vue stagiaire : son rapport (ou celui de son binôme) ou celui d'un autre
            'mine'   => $forStaff ? null : in_array($viewerId, array_filter([$ownerId, $twin?->stage_request_id]), true),
            'author' => $forStaff ? null : $report->stage->fullName(),
            'stage' => $forStaff ? [
                'id' => $report->stage->id, 'reference' => $report->stage->reference,
                'nom_complet' => trim($report->stage->civilite . ' ' . $report->stage->fullName()),
            ] : null,
            'decisions' => $forStaff && $by ? $this->decisionOptions($report, $by) : null,
            'versions' => $versions->map(fn ($v) => [
                'version' => $v->version, 'name' => $v->original_name, 'size' => $v->size, 'note' => $v->note,
                'late' => $v->late, 'post_defense' => (bool) $v->post_defense, 'at' => $v->created_at?->toIso8601String(),
                'by' => $v->submitted_by && $v->submitted_by !== $ownerId ? ($interns[$v->submitted_by] ?? null) : null,
            ])->values(),
            'comments' => $comments->map(fn ($c) => [
                'id' => $c->id, 'version' => $c->version, 'author' => $c->author,
                'mine' => !$forStaff && $c->author === 'stagiaire' && ((int) ($c->stage_request_id ?? 0) ?: $ownerId) === $viewerId,
                'can_delete' => $forStaff || ($c->author === 'stagiaire' && ((int) ($c->stage_request_id ?? 0) ?: $ownerId) === $viewerId),
                'name' => $c->author === 'staff' ? trim($c->first_name . ' ' . $c->last_name)
                    : ($interns[$c->stage_request_id ?? 0] ?? $report->stage->fullName()),
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
        $q = StageReport::with('stage')->orderByDesc('submitted_at')->limit(400);
        match ($filter) {
            'to_review' => $this->pending($q),
            'approved'  => $q->whereIn('status', ['approved', 'supervisor_approved'])
                ->where(fn ($w) => $w->whereNull('correction_status')->orWhere('correction_status', '!=', 'pending')),
            'rejected'  => $q->whereIn('status', ['rejected', 'supervisor_rejected']),
            default     => null,
        };
        if ($mineOf) {
            $q->whereIn('stage_request_id', DB::table('stage_supervisions')->where('user_id', $mineOf->id)->select('stage_request_id'));
        }

        $rows = $q->get();
        // Rapport de binôme : une seule ligne pour les deux stagiaires.
        $ids = $rows->pluck('id')->flip();
        $rows = $rows->reject(fn ($r) => $r->pair_report_id && isset($ids[$r->pair_report_id]) && $r->id > $r->pair_report_id && $this->twinOf($r));

        $today = BusinessHours::today()->toDateString();

        return $rows->map(fn ($r) => $this->summary($r) + [
            'stage' => ['id' => $r->stage->id, 'reference' => $r->stage->reference, 'nom_complet' => trim($r->stage->civilite . ' ' . $r->stage->fullName()),
                // Stage terminé (date de fin passée) ou arrêté : rangé à part des stagiaires en cours.
                'termine' => $r->stage->status !== StageStatus::ACCEPTED || ($r->stage->date_fin && $r->stage->date_fin->toDateString() < $today)],
        ]);
    }

    /**
     * Rapports à traiter : déposés et pas encore validés, ou version corrigée
     * d'un rapport validé à examiner. Un rapport validé (par le responsable ou
     * un superviseur) ou renvoyé au stagiaire n'y figure plus.
     */
    public function pending(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where(fn ($w) => $w->where('status', 'submitted')->orWhere('correction_status', 'pending'));
    }

    /**
     * Rapports de tous, pour l'espace stagiaire : les stagiaires en cours
     * (une puce par personne) et les rapports de la personne choisie, ou les
     * derniers déposés.
     */
    public function board(?int $personId): array
    {
        $today = BusinessHours::today()->toDateString();
        $people = StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->where('date_fin', '>=', $today)->orderBy('nom')->orderBy('prenoms')->get();
        $counts = StageReport::whereIn('stage_request_id', $people->pluck('id'))
            ->selectRaw('stage_request_id, count(*) as n')->groupBy('stage_request_id')->pluck('n', 'stage_request_id');

        $q = StageReport::with('stage')->orderByDesc('submitted_at')->orderByDesc('id');
        $q = $personId ? $q->where('stage_request_id', $personId) : $q->whereIn('stage_request_id', $people->pluck('id'))->limit(60);
        $rows = $q->get();
        if (!$personId) {
            // Rapport de binôme : une seule carte pour les deux.
            $ids = $rows->pluck('id')->flip();
            $rows = $rows->reject(fn ($r) => $r->pair_report_id && isset($ids[$r->pair_report_id]) && $r->id > $r->pair_report_id && $this->twinOf($r));
        }
        $comments = DB::table('stage_report_comments')->whereIn('report_id', $rows->pluck('id'))
            ->selectRaw('report_id, count(*) as n')->groupBy('report_id')->pluck('n', 'report_id');

        return [
            'people'  => $people->map(fn ($r) => ['id' => $r->id, 'name' => $r->fullName(), 'reports' => (int) ($counts[$r->id] ?? 0)])->values(),
            'reports' => $rows->map(fn ($r) => $this->summary($r) + [
                'author' => $r->stage->fullName(), 'author_id' => $r->stage_request_id, 'comments' => (int) ($comments[$r->id] ?? 0),
            ])->values(),
        ];
    }

    /**
     * Rapport validé : les versions déposées avant la version validée sont
     * supprimées (fichiers compris) pour libérer de la place sur le serveur.
     * Restent la version validée et, le cas échéant, une version corrigée
     * déposée après. Un fichier partagé avec le rapport du binôme n'est
     * effacé que lorsque plus aucune version ne l'utilise.
     */
    public function pruneOldVersions(StageReport $r): int
    {
        if (!$r->official_version) {
            return 0;
        }
        $old = StageReportVersion::where('report_id', $r->id)->where('version', '<', $r->official_version)->get(['id', 'file_path']);
        if ($old->isEmpty()) {
            return 0;
        }
        try {
            StageReportVersion::whereIn('id', $old->pluck('id'))->delete();
            $disk = Storage::disk(StageRequestService::DISK);
            foreach ($old->pluck('file_path')->unique() as $path) {
                if (!StageReportVersion::where('file_path', $path)->exists()) {
                    $disk->delete($path);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[Stages] Anciennes versions de rapport non supprimées', ['report_id' => $r->id, 'error' => $e->getMessage()]);

            return 0;
        }

        return $old->count();
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

        // Rapports déjà validés qui gardent d'anciennes versions (avant cette règle).
        if ($reminders) {
            $ids = StageReportVersion::query()->join('stage_reports as r', 'r.id', '=', 'stage_report_versions.report_id')
                ->whereNotNull('r.official_version')->whereColumn('stage_report_versions.version', '<', 'r.official_version')
                ->distinct()->limit(200)->pluck('stage_report_versions.report_id');
            StageReport::whereIn('id', $ids)->get()->each(fn ($r) => $this->pruneOldVersions($r));
        }

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
                        if ($type === 'open' && $p['kind'] === 'final') {
                            $this->announceFinalOpening($req, $p);
                        }
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
        );

        // Stagiaire arrivé avant le système : pas de rapports périodiques pour
        // les périodes terminées avant son entrée ; le rapport final reste dû.
        if ($req->attendance_from) {
            $from = $req->attendance_from->toDateString();
            $periods = array_values(array_filter($periods, fn ($p) => $p['kind'] === 'final' || ($p['end'] ?? '') >= $from));
        }

        // Une fois ouvert, l'espace reste ouvert : une prolongation du stage qui décale les 2/3
        // ne le referme pas.
        $last = array_key_last($periods);
        if ($last !== null && $periods[$last]['opens'] > BusinessHours::today()->toDateString()) {
            $openedAt = DB::table('stage_report_notices')->where('stage_request_id', $req->id)
                ->where('period_key', ReportPlan::FINAL_KEY)->where('kind', 'open')->value('created_at');
            if ($openedAt) {
                $periods[$last]['opens'] = min($periods[$last]['opens'], CarbonImmutable::parse($openedAt)->setTimezone(BusinessHours::TZ)->toDateString());
            }
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

    public function isValidated(StageReport $r): bool
    {
        return $r->status === 'approved' || $r->status === 'supervisor_approved';
    }

    private function leadDecision(StageReport $r): ?string
    {
        return DB::table('stage_report_reviews')->where('report_id', $r->id)->where('version', $r->current_version)
            ->where('role', 'responsable')->orderByDesc('id')->value('decision');
    }

    private function state(StageRequest $req, array $p, ?StageReport $report, CarbonImmutable $today): string
    {
        if ($report) {
            return $this->isValidated($report) ? 'validated'
                : (in_array($report->status, ['rejected', 'supervisor_rejected'], true) ? 'to_fix' : 'deposited');
        }
        if ($today->toDateString() < $p['opens']) {
            return 'upcoming';
        }

        // Entré dans le système après l'échéance (stage enregistré après coup) : à déposer, pas manquant.
        return $this->isLate($req, $p, $today) ? 'missing' : 'open';
    }

    public function labelFor(StageReport $r): string
    {
        if ($r->kind === 'final') {
            return 'Rapport de fin de stage';
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
            'mid'     => ['Rapport de fin de stage : plus que 7 jours', 'Votre stage se termine dans 7 jours. Déposez votre rapport de fin de stage en PDF depuis votre espace stagiaire.'],
            'late'    => ['Rapport en retard', 'Le délai de ce rapport est passé et il n\'a pas été déposé. Déposez-le dès que possible : il sera accepté, mais marqué en retard.'],
            default   => $final
                ? ['Rapport de fin de stage à déposer', 'Le dépôt de votre rapport de fin de stage est ouvert. Vous pouvez déposer une première version dès maintenant, autant de fois que nécessaire : vos encadrants la liront et la commenteront, puis vous la corrigerez avec eux jusqu\'à sa validation. Déposez-la assez tôt pour qu\'elle soit validée avant la fin de votre stage.']
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

    /** Ouverture du rapport de fin de stage : les superviseurs du stagiaire et les responsables en sont prévenus, une seule fois. */
    private function announceFinalOpening(StageRequest $req, array $p): void
    {
        $this->notifier->toFollowers($req, 'Rapport de fin de stage ouvert', [
            'Le dépôt du rapport de fin de stage de ' . $req->fullName() . ' est ouvert. Vous serez prévenu à chaque version déposée, pour la lire et la commenter.',
        ], ['Stagiaire' => $req->fullName(), 'À déposer avant le' => $this->requests->frDate($p['closes'])],
            'stage-rapport', rtrim((string) config('app.url'), '/') . '/services/stages/rapports');
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

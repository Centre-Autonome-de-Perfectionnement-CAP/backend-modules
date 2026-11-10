<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageAccess;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Évaluation des stagiaires par les superviseurs (note sur 20).
 *
 *  - 10 jours ouvrés avant la fin du stage, les superviseurs sont invités à
 *    noter : obligatoire pour les superviseurs titulaires (rappel tous les
 *    2 jours ouvrés), facultatif pour les autres ;
 *  - barème : points par critère, total sur 20, commentaire obligatoire ;
 *  - moyenne : titulaires 60 %, autres superviseurs 40 % (100 % pour un
 *    seul des deux groupes), puis note retenue au demi-point (voir retained()) ;
 *  - le responsable de division voit tout le détail (lui seul), renvoie une
 *    note à revoir ou valide avec une appréciation ; le stagiaire reçoit
 *    alors son bulletin (WhatsApp : message puis PDF ; email), sans noms
 *    ni commentaires des superviseurs.
 */
class EvaluationService
{
    /** clé => [libellé, points] ; total 20. */
    public const CRITERIA = [
        'assiduite'    => ['Assiduité et ponctualité', 4],
        'qualite'      => ['Qualité du travail', 6],
        'autonomie'    => ['Autonomie', 4],
        'comportement' => ["Comportement et esprit d'équipe", 3],
        'progression'  => ['Progression', 3],
    ];
    private const OPEN_DAYS = 10;      // jours ouvrés avant la fin
    private const REMIND_DAYS = 2;
    private const TITULAR_WEIGHT = 0.6;
    private const DISK = 'local';

    public function __construct(
        private readonly StageNotifier $notifier,
        private readonly StageDocumentService $documents,
        private readonly InternActivationService $activation,
        private readonly FeedbackService $feedback,
        private readonly StagePdfService $pdf,
    ) {}

    /**
     * Note retenue, toujours au demi-point, arrondie vers le haut au-delà de
     * x,50 : de x,00 à x,24 → x,00 ; de x,25 à x,50 → x,50 ; de x,51 à x,99 → x+1.
     * La moyenne est d'abord arrondie au centième.
     */
    public static function retained(float $average): float
    {
        $v = round($average, 2);
        $base = floor($v);
        $f = round($v - $base, 2);

        return min(20.0, $f < 0.25 ? $base : ($f <= 0.5 ? $base + 0.5 : $base + 1));
    }

    // ════════════════════════════════════════════════════════════════════
    // CALENDRIER (tâche quotidienne)
    // ════════════════════════════════════════════════════════════════════

    /** Ouvre les évaluations des stages qui finissent bientôt et relance les titulaires. */
    public function tick(): array
    {
        if (!$this->documents->ready()) {
            return ['opened' => 0, 'reminded' => 0];
        }
        $today = BusinessHours::today();
        $opened = $reminded = 0;
        $stages = StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->whereNotNull('date_fin')->where('date_fin', '>=', $today->toDateString())
            ->where('date_fin', '<=', $today->addDays(20)->toDateString())->get();

        foreach ($stages as $req) {
            $end = CarbonImmutable::parse($req->date_fin->toDateString(), BusinessHours::TZ);
            if ($this->activation->workingDaysBetween($today, $end) > self::OPEN_DAYS) {
                continue;
            }
            $row = DB::table('stage_evaluation_results')->where('stage_request_id', $req->id)->first();
            if (!$row) {
                DB::table('stage_evaluation_results')->insert(['stage_request_id' => $req->id, 'opened_at' => now(), 'reminded_at' => now(),
                    'created_at' => now(), 'updated_at' => now()]);
                $this->askTitulars($req, $this->missingTitulars($req), false);
                if ($this->feedback->endPeriodOpen($req)) {
                    $this->notifier->toDemandeur($req, 'Votre avis sur le stage', [
                        'Votre stage se termine bientôt. Donnez votre avis : notez votre stage, de façon anonyme, en une minute.',
                    ], ctaLabel: 'Noter mon stage', ctaUrl: $this->notifier->spaceUrl('/stage'), context: 'stage-notation');
                }
                $opened++;
                continue;
            }
            if ($row->validated_at || $row->ready_at) {
                continue;
            }
            $missing = $this->missingTitulars($req);
            $last = CarbonImmutable::parse($row->reminded_at ?? $row->opened_at)->setTimezone(BusinessHours::TZ);
            if ($missing->isNotEmpty() && $this->activation->workingDaysBetween($last, $today) >= self::REMIND_DAYS) {
                $this->askTitulars($req, $missing, true);
                DB::table('stage_evaluation_results')->where('id', $row->id)->update(['reminded_at' => now(), 'updated_at' => now()]);
                $reminded++;
            }
        }

        return ['opened' => $opened, 'reminded' => $reminded];
    }

    // ════════════════════════════════════════════════════════════════════
    // SUPERVISEURS
    // ════════════════════════════════════════════════════════════════════

    /** Stagiaires à noter (évaluation ouverte, pas encore validée) : les siens d'abord. */
    public function toRate(User $user): array
    {
        $this->documents->assertReady();
        $rows = DB::table('stage_evaluation_results')->whereNull('validated_at')->get()->keyBy('stage_request_id');
        $reqs = StageRequest::whereIn('id', $rows->keys())->orderBy('date_fin')->get();
        $mine = DB::table('stage_supervisions')->where('user_id', $user->id)->pluck('stage_request_id')->flip();
        $given = DB::table('stage_evaluations')->where('user_id', $user->id)->whereIn('stage_request_id', $rows->keys())->get()->keyBy('stage_request_id');

        return [
            'criteria' => $this->criteriaList(),
            'interns'  => $reqs->map(fn ($r) => [
                'id' => $r->id, 'name' => $r->fullName(), 'reference' => $r->reference, 'date_fin' => $r->date_fin?->toDateString(),
                'mine' => isset($mine[$r->id]),
                'mine_evaluation' => ($e = $given[$r->id] ?? null) ? [
                    'scores' => json_decode($e->scores, true), 'total' => (float) $e->total, 'comment' => $e->comment,
                    'returned_note' => $e->returned_at ? $e->returned_note : null,
                ] : null,
            ])->sortBy(fn ($r) => $r['mine'] ? 0 : 1)->values(),
        ];
    }

    /** @param array<string, float|int|string> $scores */
    public function save(StageRequest $req, User $user, array $scores, string $comment): void
    {
        $this->documents->assertReady();
        $row = DB::table('stage_evaluation_results')->where('stage_request_id', $req->id)->first();
        if (!$row) {
            throw new BusinessException('La notation de ce stagiaire n\'est pas encore ouverte.', 'STAGE_EVAL_CLOSED', 422);
        }
        if ($row->validated_at) {
            throw new BusinessException('La note de ce stagiaire est déjà validée.', 'STAGE_EVAL_VALIDATED', 409);
        }
        $clean = [];
        foreach (self::CRITERIA as $k => [$label, $max]) {
            $v = (float) ($scores[$k] ?? -1);
            if ($v < 0 || $v > $max || fmod($v * 2, 1.0) !== 0.0) {
                throw new BusinessException("{$label} : une note de 0 à {$max}, par demi-point.", 'STAGE_EVAL_SCORE', 422);
            }
            $clean[$k] = $v;
        }
        $titular = DB::table('stage_supervisions')->where('stage_request_id', $req->id)->where('user_id', $user->id)->exists();

        DB::table('stage_evaluations')->updateOrInsert(['stage_request_id' => $req->id, 'user_id' => $user->id], [
            'titular' => $titular, 'scores' => json_encode($clean), 'total' => array_sum($clean), 'comment' => trim($comment),
            'returned_note' => null, 'returned_at' => null, 'updated_at' => now(),
            'created_at' => DB::table('stage_evaluations')->where('stage_request_id', $req->id)->where('user_id', $user->id)->value('created_at') ?? now(),
        ]);

        // Toutes les notes des titulaires reçues : le responsable est prévenu.
        if (!$row->ready_at && $this->missingTitulars($req)->isEmpty()) {
            DB::table('stage_evaluation_results')->where('id', $row->id)->update(['ready_at' => now(), 'updated_at' => now()]);
            $this->notifier->toManagersGeneric('Note de stage à valider', [
                'Les superviseurs ont noté ' . $req->fullName() . ' : la note est prête à être validée.',
            ], ['Référence' => $req->reference, 'Stagiaire' => $req->fullName()], 'stage-evaluation',
                rtrim((string) config('app.url'), '/') . '/services/stages/evaluations', $req->reference);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // RESPONSABLE DE DIVISION
    // ════════════════════════════════════════════════════════════════════

    /** Toutes les évaluations ouvertes (et validées récemment), avec le détail. */
    public function board(): array
    {
        $this->documents->assertReady();
        $rows = DB::table('stage_evaluation_results')
            ->where(fn ($q) => $q->whereNull('validated_at')->orWhere('validated_at', '>=', now()->subDays(60)))
            ->orderByRaw('validated_at is not null')->orderBy('opened_at')->get();
        $reqs = StageRequest::whereIn('id', $rows->pluck('stage_request_id'))->get()->keyBy('id');

        return [
            'criteria' => $this->criteriaList(),
            'items' => $rows->filter(fn ($r) => isset($reqs[$r->stage_request_id]))->map(fn ($r) => $this->detail($reqs[$r->stage_request_id], $r))->values(),
        ];
    }

    public function returnTo(StageRequest $req, int $userId, string $note, User $by): void
    {
        $this->assertOpen($req);
        $e = DB::table('stage_evaluations')->where('stage_request_id', $req->id)->where('user_id', $userId)->first();
        if (!$e) {
            throw new BusinessException('Ce superviseur n\'a pas encore noté.', 'STAGE_EVAL_NONE', 404);
        }
        DB::table('stage_evaluations')->where('id', $e->id)->update(['returned_note' => trim($note), 'returned_at' => now(), 'updated_at' => now()]);
        DB::table('stage_evaluation_results')->where('stage_request_id', $req->id)->update(['ready_at' => null, 'updated_at' => now()]);
        if ($u = User::find($userId)) {
            $this->notifier->toStaff(collect([$u]), 'Note de stage à revoir', [
                trim($by->first_name . ' ' . $by->last_name) . ' vous demande de revoir votre note de ' . $req->fullName() . '.',
            ], ['Stagiaire' => $req->fullName(), 'Remarque' => trim($note)], 'stage-evaluation',
                rtrim((string) config('app.url'), '/') . '/services/stages/evaluations', $req->reference);
        }
    }

    public function validate(StageRequest $req, string $appreciation, User $by): array
    {
        $row = $this->assertOpen($req);
        $evals = DB::table('stage_evaluations')->where('stage_request_id', $req->id)->get();
        if ($evals->isEmpty()) {
            throw new BusinessException('Aucun superviseur n\'a encore noté ce stagiaire.', 'STAGE_EVAL_NONE', 422);
        }
        if ($evals->whereNotNull('returned_at')->isNotEmpty()) {
            throw new BusinessException('Une note renvoyée pour être revue n\'a pas encore été corrigée.', 'STAGE_EVAL_RETURNED', 409);
        }
        $c = $this->compute($evals);
        DB::table('stage_evaluation_results')->where('id', $row->id)->update([
            'average' => $c['average'], 'final' => $c['final'], 'criteria' => json_encode($c['criteria']),
            'appreciation' => trim($appreciation), 'validated_by' => $by->id, 'validated_at' => now(), 'updated_at' => now(),
        ]);

        $file = $this->bulletin($req);
        $this->notifier->toDemandeur($req, 'Votre note de stage', [
            'Votre note de stage a été validée : ' . $this->fr($c['final']) . '/20.',
            'Votre bulletin d\'évaluation est joint. Vous le retrouvez aussi dans « Mon stage ».',
        ], ctaLabel: 'Ouvrir mon espace', ctaUrl: $this->notifier->spaceUrl('/stage'), context: 'stage-evaluation', file: $file, official: true);

        return $c;
    }

    // ════════════════════════════════════════════════════════════════════
    // STAGIAIRE
    // ════════════════════════════════════════════════════════════════════

    /** Note validée du stagiaire (sans noms ni commentaires des superviseurs). */
    public function forIntern(StageRequest $req): ?array
    {
        if (!$this->documents->ready()) {
            return null;
        }
        $row = DB::table('stage_evaluation_results')->where('stage_request_id', $req->id)->whereNotNull('validated_at')->first();
        if (!$row) {
            return null;
        }
        $crit = json_decode((string) $row->criteria, true) ?: [];

        return [
            'final' => (float) $row->final, 'appreciation' => $row->appreciation, 'validated_at' => $row->validated_at,
            'criteria' => collect(self::CRITERIA)->map(fn ($c, $k) => ['label' => $c[0], 'max' => $c[1], 'score' => $crit[$k] ?? null])->values(),
        ];
    }

    /** Bulletin PDF (rangé dans le dossier) ; null s'il n'existe pas ou ne peut être produit. */
    public function bulletin(StageRequest $req): ?array
    {
        $row = DB::table('stage_evaluation_results')->where('stage_request_id', $req->id)->whereNotNull('validated_at')->first();
        if (!$row) {
            return null;
        }
        try {
            $disk = Storage::disk(self::DISK);
            if (!$row->pdf_path || !$disk->exists($row->pdf_path)) {
                $pdf = $this->pdf->evaluation($req, $this->forIntern($req), User::find($row->validated_by), CarbonImmutable::parse($row->validated_at));
                $path = "stages/{$req->reference}/evaluation.pdf";
                $disk->put($path, $pdf['content']);
                DB::table('stage_evaluation_results')->where('id', $row->id)->update(['pdf_path' => $path]);
                $row->pdf_path = $path;
            }

            return ['disk' => self::DISK, 'path' => $row->pdf_path, 'name' => "Bulletin d'evaluation de stage - {$req->reference}.pdf",
                'caption' => "Bulletin d'évaluation de stage"];
        } catch (\Throwable $e) {
            Log::error('[Stages] Bulletin d\'évaluation non généré', ['reference' => $req->reference, 'error' => $e->getMessage()]);

            return null;
        }
    }

    // ── Outils ────────────────────────────────────────────────────────────

    /** Moyenne pondérée, note retenue et moyenne de chaque critère. */
    public function compute(Collection $evals): array
    {
        $groups = [$evals->where('titular', true), $evals->where('titular', false)];
        $weights = $groups[0]->isEmpty() || $groups[1]->isEmpty() ? [1.0, 1.0] : [self::TITULAR_WEIGHT, 1 - self::TITULAR_WEIGHT];
        $mean = function (callable $value) use ($groups, $weights) {
            $sum = 0.0;
            foreach ($groups as $i => $g) {
                if ($g->isNotEmpty()) {
                    $sum += $weights[$i] * $g->avg($value);
                }
            }

            return $sum;
        };
        $scores = fn ($e) => json_decode($e->scores, true);
        $average = round($mean(fn ($e) => (float) $e->total), 2);

        return [
            'average'  => $average,
            'final'    => self::retained($average),
            'criteria' => collect(self::CRITERIA)->mapWithKeys(fn ($c, $k) => [$k => round($mean(fn ($e) => (float) ($scores($e)[$k] ?? 0)), 2)])->all(),
        ];
    }

    private function detail(StageRequest $req, object $row): array
    {
        $evals = DB::table('stage_evaluations')->where('stage_request_id', $req->id)->get();
        $users = User::whereIn('id', $evals->pluck('user_id'))->get()->keyBy('id');
        $missing = $this->missingTitulars($req);

        return [
            'id' => $req->id, 'name' => $req->fullName(), 'reference' => $req->reference, 'date_fin' => $req->date_fin?->toDateString(),
            'validated' => $row->validated_at !== null, 'validated_at' => $row->validated_at, 'appreciation' => $row->appreciation,
            'final' => $row->final !== null ? (float) $row->final : null,
            'preview' => $evals->isEmpty() ? null : $this->compute($evals),
            'missing' => $missing->map(fn ($u) => trim($u->first_name . ' ' . $u->last_name))->values(),
            'evaluations' => $evals->map(fn ($e) => [
                'user_id' => (int) $e->user_id, 'name' => ($u = $users[$e->user_id] ?? null) ? trim($u->first_name . ' ' . $u->last_name) : 'Superviseur',
                'titular' => (bool) $e->titular, 'scores' => json_decode($e->scores, true), 'total' => (float) $e->total,
                'comment' => $e->comment, 'returned' => $e->returned_at !== null, 'returned_note' => $e->returned_note,
            ])->sortByDesc('titular')->values(),
        ];
    }

    private function assertOpen(StageRequest $req): object
    {
        $row = DB::table('stage_evaluation_results')->where('stage_request_id', $req->id)->first();
        if (!$row) {
            throw new BusinessException('La notation de ce stagiaire n\'est pas ouverte.', 'STAGE_EVAL_CLOSED', 422);
        }
        if ($row->validated_at) {
            throw new BusinessException('La note de ce stagiaire est déjà validée.', 'STAGE_EVAL_VALIDATED', 409);
        }

        return $row;
    }

    /** Superviseurs titulaires qui n'ont pas encore noté (ou dont la note est à revoir). */
    private function missingTitulars(StageRequest $req): Collection
    {
        $done = DB::table('stage_evaluations')->where('stage_request_id', $req->id)->whereNull('returned_at')->pluck('user_id');

        return User::whereIn('id', DB::table('stage_supervisions')->where('stage_request_id', $req->id)->pluck('user_id'))
            ->whereNotIn('id', $done)->get();
    }

    private function askTitulars(StageRequest $req, Collection $users, bool $reminder): void
    {
        if ($users->isEmpty()) {
            return;
        }
        $this->notifier->toStaff($users, $reminder ? 'Rappel : note de stage à donner' : 'Note de stage à donner', [
            'Le stage de ' . $req->fullName() . ' se termine le ' . $req->date_fin->locale('fr')->isoFormat('D MMMM YYYY') . '.',
            'Donnez-lui sa note sur 20 (barème par critère, avec un commentaire). Elle est obligatoire pour vos stagiaires.',
        ], ['Stagiaire' => $req->fullName(), 'Référence' => $req->reference], 'stage-evaluation',
            rtrim((string) config('app.url'), '/') . '/services/stages/evaluations', $req->reference);
    }

    private function criteriaList(): Collection
    {
        return collect(self::CRITERIA)->map(fn ($c, $k) => ['key' => $k, 'label' => $c[0], 'max' => $c[1]])->values();
    }

    private function fr(float $v): string
    {
        return number_format($v, 2, ',', ' ');
    }
}

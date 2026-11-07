<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Espace tâches : une tâche est créée par un superviseur (ou le stagiaire
 * lui-même) pour un stagiaire ou son binôme. Le stagiaire remet son travail
 * (fichiers ou liens ; seule la dernière remise est gardée), le personnel
 * valide ou demande de revoir, puis peut rendre public un travail validé :
 * il apparaît dans « Travaux publiés » pour tous les stagiaires et le personnel.
 */
class TaskService
{
    public const MAX_ITEMS   = 5;
    public const MAX_FILE_KB = 20480;   // 20 Mo par fichier
    public const FILE_TYPES  = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'jpg', 'jpeg', 'png', 'webp'];
    public const FILE_MIMES  = [
        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip', 'application/x-zip-compressed', 'image/jpeg', 'image/png', 'image/webp',
        // Fichiers Office reconnus comme archives par certains serveurs
        'application/octet-stream',
    ];
    public const STATUS_LABELS = ['todo' => 'À faire', 'submitted' => 'Remis', 'revision' => 'À revoir', 'validated' => 'Validé'];
    private const DISK = 'local';

    public function __construct(private readonly StageNotifier $notifier) {}

    /** Tables présentes (migration passée) ? */
    public function ready(): bool
    {
        if (Cache::get('stage-tasks-ready')) {
            return true;
        }
        $ok = Schema::hasTable('stage_tasks') && Schema::hasTable('stage_task_members') && Schema::hasTable('stage_task_items');
        if ($ok) {
            Cache::put('stage-tasks-ready', true, 3600);
        }

        return $ok;
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE
    // ════════════════════════════════════════════════════════════════════

    /** Tâches d'un stagiaire (les siennes et celles de son binôme). */
    public function forIntern(StageRequest $req): Collection
    {
        $this->assertReady();
        $ids = DB::table('stage_task_members')->where('stage_request_id', $req->id)->pluck('task_id');

        return $this->summaries(DB::table('stage_tasks')->whereIn('id', $ids)->orderByRaw("status = 'validated'")
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderByDesc('id')->get());
    }

    /** Progiciel : toutes les tâches, ou celles des stagiaires suivis ($mine). */
    public function forStaff(?User $mine, string $status = 'all'): Collection
    {
        $this->assertReady();
        $q = DB::table('stage_tasks')->orderByRaw("FIELD(status, 'submitted', 'revision', 'todo', 'validated')")
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderByDesc('id');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($mine) {
            $followed = DB::table('stage_supervisions')->where('user_id', $mine->id)->pluck('stage_request_id');
            $q->whereIn('id', DB::table('stage_task_members')->whereIn('stage_request_id', $followed)->select('task_id'));
        }

        return $this->summaries($q->limit(300)->get());
    }

    /** Travaux publiés, les plus récents d'abord. */
    public function published(): Collection
    {
        $this->assertReady();

        return $this->summaries(DB::table('stage_tasks')->whereNotNull('published_at')->orderByDesc('published_at')->limit(200)->get());
    }

    public function find(int $id): object
    {
        $this->assertReady();
        $t = DB::table('stage_tasks')->find($id);
        if (!$t) {
            throw new BusinessException('Cette tâche n\'existe plus.', 'STAGE_TASK_NOT_FOUND', 404);
        }

        return $t;
    }

    /** Le stagiaire peut-il voir la tâche ? (la sienne, celle de son binôme, ou un travail publié) */
    public function internCanSee(object $task, StageRequest $req): bool
    {
        return $task->published_at !== null || $this->isMember($task, $req);
    }

    public function isMember(object $task, StageRequest $req): bool
    {
        return DB::table('stage_task_members')->where('task_id', $task->id)->where('stage_request_id', $req->id)->exists();
    }

    /** Détail ; $viewer : stagiaire qui consulte (null : personnel). */
    public function detail(object $task, ?StageRequest $viewer, ?User $staff = null): array
    {
        $member = $viewer && $this->isMember($task, $viewer);
        $items = DB::table('stage_task_items')->where('task_id', $task->id)->orderBy('id')->get();
        $summary = $this->summaries(collect([$task]))->first();

        return $summary + [
            'instructions'   => $task->instructions,
            'message'        => $task->message,
            'review_comment' => $task->review_comment,
            'reviewed_by'    => $task->reviewed_by ? $this->staffName($task->reviewed_by) : null,
            'reviewed_at'    => $task->reviewed_at ? CarbonImmutable::parse($task->reviewed_at)->toIso8601String() : null,
            'items'          => $items->map(fn ($i) => [
                'id' => (int) $i->id, 'kind' => $i->kind, 'name' => $i->name, 'url' => $i->url,
                'mime' => $i->mime, 'size' => (int) $i->size,
            ])->values(),
            'mine'        => $member,
            'can_submit'  => $member && $task->status !== 'validated',
            'can_delete'  => $staff !== null || ($member && $task->created_by_type === 'intern' && (int) $task->version === 0),
            'can_review'  => $staff !== null && $task->status === 'submitted',
            'can_publish' => $staff !== null && $task->status === 'validated',
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // CRÉATION ET SUPPRESSION
    // ════════════════════════════════════════════════════════════════════

    /**
     * Nouvelle tâche pour $for (et son binôme si $withPartner).
     * $staff : créée par le personnel (le stagiaire est prévenu) ; sinon par le stagiaire lui-même.
     */
    public function create(StageRequest $for, string $title, ?string $instructions, ?string $due, bool $withPartner, ?User $staff): object
    {
        $this->assertReady();
        if ($for->status !== StageStatus::ACCEPTED) {
            throw new BusinessException('Ce stagiaire n\'est pas en stage.', 'STAGE_TASK_NOT_INTERN', 422);
        }
        $title = trim(strip_tags($title));
        if ($title === '') {
            throw new BusinessException('Le titre est obligatoire.', 'STAGE_TASK_TITLE', 422);
        }
        if ($due !== null && $due < BusinessHours::today()->toDateString()) {
            throw new BusinessException('L\'échéance ne peut pas être passée.', 'STAGE_TASK_DUE', 422);
        }
        $members = collect([$for]);
        if ($withPartner && $for->team_id) {
            $members = $members->concat(StageRequest::where('team_id', $for->team_id)->where('id', '!=', $for->id)
                ->where('status', StageStatus::ACCEPTED)->get());
        }

        $id = DB::transaction(function () use ($title, $instructions, $due, $staff, $for, $members) {
            $id = DB::table('stage_tasks')->insertGetId([
                'title' => mb_substr($title, 0, 200), 'instructions' => $this->clean($instructions), 'due_date' => $due,
                'created_by_type' => $staff ? 'staff' : 'intern', 'created_by_id' => $staff?->id ?? $for->id,
                'status' => 'todo', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('stage_task_members')->insert($members->map(fn ($m) => ['task_id' => $id, 'stage_request_id' => $m->id])->all());

            return $id;
        });
        $task = $this->find($id);

        if ($staff) {
            foreach ($members as $m) {
                $this->notifier->toDemandeur($m, 'Nouvelle tâche', [
                    trim($staff->first_name . ' ' . $staff->last_name) . ' vous a confié une tâche' . ($members->count() > 1 ? ' (avec votre binôme).' : '.'),
                ], ['Tâche' => $title, 'Échéance' => $due ? $this->frDate($due) : null],
                    highlight: $task->instructions, highlightTitle: 'Consignes',
                    ctaLabel: 'Ouvrir la tâche', ctaUrl: $this->notifier->spaceUrl("/taches/{$id}"), context: 'stage-tache');
            }
        }

        return $task;
    }

    /** Suppression : le personnel, ou le stagiaire pour une tâche qu'il a créée et pas encore remise. */
    public function delete(object $task, ?StageRequest $intern, ?User $staff): void
    {
        if (!$staff && !($intern && $this->isMember($task, $intern) && $task->created_by_type === 'intern' && (int) $task->version === 0)) {
            throw new BusinessException('Vous ne pouvez supprimer que vos propres tâches, avant toute remise.', 'STAGE_TASK_FORBIDDEN', 403);
        }
        $paths = DB::table('stage_task_items')->where('task_id', $task->id)->whereNotNull('path')->pluck('path');
        DB::transaction(function () use ($task) {
            DB::table('stage_task_items')->where('task_id', $task->id)->delete();
            DB::table('stage_task_members')->where('task_id', $task->id)->delete();
            DB::table('stage_tasks')->where('id', $task->id)->delete();
        });
        $this->deleteFiles($paths);
    }

    // ════════════════════════════════════════════════════════════════════
    // REMISE, REVUE, PUBLICATION
    // ════════════════════════════════════════════════════════════════════

    /**
     * Remise du travail : remplace la remise précédente (seule la dernière est gardée).
     *
     * @param UploadedFile[] $files
     * @param string[]       $links
     */
    public function submit(object $task, StageRequest $by, array $files, array $links, ?string $message): object
    {
        if (!$this->isMember($task, $by)) {
            throw new BusinessException('Cette tâche ne vous est pas confiée.', 'STAGE_TASK_FORBIDDEN', 403);
        }
        if ($task->status === 'validated') {
            throw new BusinessException('Ce travail est déjà validé.', 'STAGE_TASK_VALIDATED', 409);
        }
        $links = array_values(array_filter(array_map(fn ($l) => trim((string) $l), $links)));
        if (!$files && !$links) {
            throw new BusinessException('Ajoutez au moins un fichier ou un lien.', 'STAGE_TASK_EMPTY', 422);
        }
        if (count($files) + count($links) > self::MAX_ITEMS) {
            throw new BusinessException('Cinq fichiers ou liens au plus.', 'STAGE_TASK_TOO_MANY', 422);
        }
        foreach ($links as $l) {
            if (!filter_var($l, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $l)) {
                throw new BusinessException('Un lien n\'est pas valide (il doit commencer par https://).', 'STAGE_TASK_LINK', 422);
            }
        }

        $stored = [];
        try {
            foreach ($files as $f) {
                $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension() ?: 'bin');
                $path = $f->storeAs('stages/taches/' . $task->id, Str::random(24) . '.' . $ext, self::DISK);
                if (!$path) {
                    throw new \RuntimeException('Enregistrement du fichier impossible');
                }
                $stored[] = ['kind' => 'file', 'path' => $path, 'url' => null, 'size' => (int) $f->getSize(),
                    'mime' => $f->getMimeType(), 'name' => $this->fileName($f->getClientOriginalName(), $ext)];
            }
            $old = DB::table('stage_task_items')->where('task_id', $task->id)->whereNotNull('path')->pluck('path');
            DB::transaction(function () use ($task, $stored, $links, $by, $message) {
                DB::table('stage_task_items')->where('task_id', $task->id)->delete();
                foreach ($stored as $s) {
                    DB::table('stage_task_items')->insert($s + ['task_id' => $task->id, 'created_at' => now()]);
                }
                foreach ($links as $l) {
                    DB::table('stage_task_items')->insert(['task_id' => $task->id, 'kind' => 'link', 'name' => mb_substr($l, 0, 255),
                        'url' => mb_substr($l, 0, 1000), 'size' => 0, 'created_at' => now()]);
                }
                DB::table('stage_tasks')->where('id', $task->id)->update([
                    'status' => 'submitted', 'version' => DB::raw('version + 1'), 'message' => $this->clean($message),
                    'submitted_at' => now(), 'submitted_by' => $by->id, 'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            $this->deleteFiles(collect($stored)->pluck('path'));
            throw $e;
        }
        // Remise précédente : effacée (seule la dernière est gardée).
        $this->deleteFiles($old);

        $task = $this->find($task->id);
        $members = $this->members($task);
        $this->notifier->toFollowers($members->all(), 'Travail remis', [
            $by->fullName() . ' a remis son travail' . ($task->version > 1 ? ' (nouvelle version).' : '.'),
        ], ['Tâche' => $task->title, 'Stagiaire(s)' => $members->map->fullName()->implode(', '), 'Version' => (string) $task->version,
            'Message' => $task->message], 'stage-tache', $this->staffUrl($task));

        return $task;
    }

    /** Revue : « validated » ou « revision » (à revoir, avec un commentaire). */
    public function review(object $task, User $by, string $decision, ?string $comment): object
    {
        if ($task->status !== 'submitted') {
            throw new BusinessException('Ce travail n\'attend pas de revue.', 'STAGE_TASK_NOT_SUBMITTED', 409);
        }
        $comment = $this->clean($comment);
        if ($decision === 'revision' && !$comment) {
            throw new BusinessException('Dites ce qu\'il faut revoir.', 'STAGE_TASK_COMMENT', 422);
        }
        DB::table('stage_tasks')->where('id', $task->id)->update([
            'status' => $decision, 'review_comment' => $comment, 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'updated_at' => now(),
        ]);
        $task = $this->find($task->id);
        $who = trim($by->first_name . ' ' . $by->last_name);
        foreach ($this->members($task) as $m) {
            $this->notifier->toDemandeur($m, $decision === 'validated' ? 'Travail validé' : 'Travail à revoir', [
                $decision === 'validated' ? "{$who} a validé votre travail." : "{$who} vous demande de revoir votre travail et de le remettre.",
            ], ['Tâche' => $task->title], highlight: $comment, highlightTitle: 'Commentaire',
                ctaLabel: 'Ouvrir la tâche', ctaUrl: $this->notifier->spaceUrl("/taches/{$task->id}"), context: 'stage-tache');
        }

        return $task;
    }

    /** Publication d'un travail validé (ou retrait). L'annonce part dans le récapitulatif du jour. */
    public function publish(object $task, User $by, bool $public): object
    {
        if ($public && $task->status !== 'validated') {
            throw new BusinessException('Seul un travail validé peut être rendu public.', 'STAGE_TASK_NOT_VALIDATED', 409);
        }
        DB::table('stage_tasks')->where('id', $task->id)->update($public
            ? ['published_at' => $task->published_at ?? now(), 'published_by' => $by->id, 'updated_at' => now()]
            : ['published_at' => null, 'published_by' => null, 'announced_at' => null, 'updated_at' => now()]);

        return $this->find($task->id);
    }

    /** Fichier rendu : [chemin, nom, type]. */
    public function file(object $task, int $itemId): array
    {
        $i = DB::table('stage_task_items')->where('task_id', $task->id)->where('id', $itemId)->where('kind', 'file')->first();
        if (!$i || !Storage::disk(self::DISK)->exists($i->path)) {
            throw new BusinessException('Ce fichier n\'est plus disponible.', 'STAGE_TASK_FILE_NOT_FOUND', 404);
        }

        return [$i->path, $i->name, $i->mime];
    }

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::DISK);
    }

    // ════════════════════════════════════════════════════════════════════
    // TÂCHES QUOTIDIENNES
    // ════════════════════════════════════════════════════════════════════

    /** Rappel la veille de l'échéance (une fois par tâche). */
    public function remindDue(?CarbonImmutable $today = null): int
    {
        if (!$this->ready()) {
            return 0;
        }
        $tomorrow = ($today ?? BusinessHours::today())->addDay()->toDateString();
        $tasks = DB::table('stage_tasks')->whereIn('status', ['todo', 'revision'])->where('due_date', $tomorrow)->whereNull('reminded_on')->get();
        foreach ($tasks as $t) {
            DB::table('stage_tasks')->where('id', $t->id)->update(['reminded_on' => $tomorrow]);
            foreach ($this->members($t) as $m) {
                $this->notifier->toDemandeur($m, 'Tâche à rendre demain', [
                    'La tâche suivante est à rendre demain. Pensez à remettre votre travail sur la plateforme.',
                ], ['Tâche' => $t->title, 'Échéance' => $this->frDate($t->due_date)],
                    ctaLabel: 'Ouvrir la tâche', ctaUrl: $this->notifier->spaceUrl("/taches/{$t->id}"), context: 'stage-tache-rappel');
            }
        }

        return $tasks->count();
    }

    /** Récapitulatif des travaux publiés du jour, à tous (un seul message). */
    public function announcePublished(): int
    {
        if (!$this->ready()) {
            return 0;
        }
        $tasks = DB::table('stage_tasks')->whereNotNull('published_at')->whereNull('announced_at')->orderBy('published_at')->get();
        if ($tasks->isEmpty()) {
            return 0;
        }
        DB::table('stage_tasks')->whereIn('id', $tasks->pluck('id'))->update(['announced_at' => now()]);
        $lines = $tasks->map(fn ($t) => '- « ' . $t->title . ' » de ' . $this->members($t)->map->fullName()->implode(' et '))->implode("\n");
        try {
            $this->notifier->toEveryone(
                $tasks->count() > 1 ? 'Nouveaux travaux publiés (' . $tasks->count() . ')' : 'Nouveau travail publié',
                [$lines, 'Consultez-les dans « Travaux publiés » sur la plateforme.'],
                $this->notifier->spaceUrl('/taches/publies'),
                rtrim((string) config('app.url'), '/') . '/services/stages/taches?vue=publies',
                'stage-travaux-publies',
            );
        } catch (\Throwable $e) {
            Log::error('[Stages] Annonce des travaux publiés impossible', ['error' => $e->getMessage()]);
        }

        return $tasks->count();
    }

    // ════════════════════════════════════════════════════════════════════
    // OUTILS
    // ════════════════════════════════════════════════════════════════════

    private function summaries(Collection $tasks): Collection
    {
        if ($tasks->isEmpty()) {
            return collect();
        }
        $ids = $tasks->pluck('id');
        $members = DB::table('stage_task_members')->whereIn('task_id', $ids)->get()->groupBy('task_id');
        $people = StageRequest::whereIn('id', $members->flatten()->pluck('stage_request_id')->unique())->get()->keyBy('id');
        $items = DB::table('stage_task_items')->whereIn('task_id', $ids)->selectRaw('task_id, count(*) as n')->groupBy('task_id')->pluck('n', 'task_id');
        $staffIds = $tasks->where('created_by_type', 'staff')->pluck('created_by_id');
        $staff = User::whereIn('id', $staffIds)->get()->mapWithKeys(fn ($u) => [$u->id => trim($u->first_name . ' ' . $u->last_name)]);

        return $tasks->map(fn ($t) => [
            'id'           => (int) $t->id,
            'title'        => $t->title,
            'due_date'     => $t->due_date,
            'status'       => $t->status,
            'status_label' => self::STATUS_LABELS[$t->status] ?? $t->status,
            'version'      => (int) $t->version,
            'submitted_at' => $t->submitted_at ? CarbonImmutable::parse($t->submitted_at)->toIso8601String() : null,
            'published'    => $t->published_at !== null,
            'published_at' => $t->published_at ? CarbonImmutable::parse($t->published_at)->toIso8601String() : null,
            'created_by'   => $t->created_by_type === 'staff'
                ? ['type' => 'staff', 'name' => $staff[$t->created_by_id] ?? 'Superviseur']
                : ['type' => 'intern', 'name' => $people[$t->created_by_id]?->fullName() ?? 'Stagiaire', 'id' => (int) $t->created_by_id],
            'members'      => ($members[$t->id] ?? collect())->map(fn ($m) => [
                'id' => (int) $m->stage_request_id, 'name' => $people[$m->stage_request_id]?->fullName() ?? 'Stagiaire',
            ])->values(),
            'items_count'  => (int) ($items[$t->id] ?? 0),
        ])->values();
    }

    /** @return Collection<int, StageRequest> */
    private function members(object $task): Collection
    {
        return StageRequest::whereIn('id', DB::table('stage_task_members')->where('task_id', $task->id)->pluck('stage_request_id'))->get();
    }

    private function deleteFiles(Collection $paths): void
    {
        foreach ($paths->filter() as $p) {
            try {
                Storage::disk(self::DISK)->delete($p);
            } catch (\Throwable $e) {
                Log::warning('[Stages] Fichier de tâche non supprimé', ['path' => $p, 'error' => $e->getMessage()]);
            }
        }
    }

    private function fileName(string $original, string $ext): string
    {
        $base = preg_replace('/[^\pL\pN\s._-]/u', '', pathinfo($original, PATHINFO_FILENAME)) ?: 'fichier';

        return mb_substr(trim($base), 0, 180) . '.' . $ext;
    }

    private function clean(?string $s): ?string
    {
        $s = trim(strip_tags((string) $s));

        return $s === '' ? null : mb_substr($s, 0, 3000);
    }

    private function staffName(int $id): string
    {
        $u = User::find($id);

        return $u ? trim($u->first_name . ' ' . $u->last_name) : 'Personnel';
    }

    private function frDate(string $d): string
    {
        return CarbonImmutable::parse($d)->locale('fr')->isoFormat('dddd D MMMM');
    }

    private function staffUrl(object $task): string
    {
        return rtrim((string) config('app.url'), '/') . '/services/stages/taches?tache=' . $task->id;
    }

    private function assertReady(): void
    {
        if (!$this->ready()) {
            throw new BusinessException('L\'espace tâches sera bientôt disponible.', 'STAGE_TASKS_NOT_READY', 409);
        }
    }
}

<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\StageAccess;
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

    /** Rôle d'un stagiaire sur une tâche. */
    public const OWNER  = 'owner';   // tâche confiée (lui et son binôme)
    public const WORKER = 'worker';  // invité pour y travailler
    public const VIEWER = 'viewer';  // invité pour voir

    public function __construct(private readonly StageNotifier $notifier) {}

    /** Tables présentes (migration passée) ? */
    public function ready(): bool
    {
        if (Cache::get('stage-tasks-ready-2')) {
            return true;
        }
        $ok = Schema::hasTable('stage_tasks') && Schema::hasTable('stage_task_items') && Schema::hasTable('stage_task_staff')
            && Schema::hasColumn('stage_task_members', 'role');
        if ($ok) {
            Cache::put('stage-tasks-ready-2', true, 3600);
        }

        return $ok;
    }

    // ════════════════════════════════════════════════════════════════════
    // LECTURE
    // ════════════════════════════════════════════════════════════════════

    /** Tâches d'un stagiaire : les siennes, celles de son binôme et celles où il est invité. */
    public function forIntern(StageRequest $req): Collection
    {
        $this->assertReady();
        $roles = DB::table('stage_task_members')->where('stage_request_id', $req->id)->pluck('role', 'task_id');

        return $this->summaries(DB::table('stage_tasks')->whereIn('id', $roles->keys())->orderByRaw("status = 'validated'")
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderByDesc('id')->get())
            ->map(fn ($t) => $t + ['my_role' => $roles[$t['id']]]);
    }

    /** Progiciel : toutes les tâches, ou celles des stagiaires suivis et celles où il est invité ($mine). */
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
            $q->where(fn ($w) => $w->whereIn('id', DB::table('stage_task_members')->whereIn('stage_request_id', $followed)->where('role', self::OWNER)->select('task_id'))
                ->orWhereIn('id', DB::table('stage_task_staff')->where('user_id', $mine->id)->select('task_id')));
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

    /** Le stagiaire peut-il voir la tâche ? (la sienne, celle de son binôme, une invitation, ou un travail publié) */
    public function internCanSee(object $task, StageRequest $req): bool
    {
        return $task->published_at !== null || $this->role($task, $req) !== null;
    }

    /** Rôle du stagiaire sur la tâche (null : aucun). */
    public function role(object $task, StageRequest $req): ?string
    {
        return DB::table('stage_task_members')->where('task_id', $task->id)->where('stage_request_id', $req->id)->value('role');
    }

    /** Peut remettre le travail : tâche confiée ou invité pour y travailler. */
    public function canWork(object $task, StageRequest $req): bool
    {
        return in_array($this->role($task, $req), [self::OWNER, self::WORKER], true);
    }

    /**
     * Le membre du personnel a-t-il la main sur la tâche (revue, publication,
     * invitations, suppression) ? Responsables, auteur de la tâche,
     * superviseurs des stagiaires concernés, et invités avec plein pouvoir.
     * Les autres la voient seulement.
     */
    public function manages(object $task, User $user): bool
    {
        return StageAccess::isLead($user)
            || ($task->created_by_type === 'staff' && (int) $task->created_by_id === (int) $user->id)
            || DB::table('stage_supervisions')->where('user_id', $user->id)->whereIn('stage_request_id',
                DB::table('stage_task_members')->where('task_id', $task->id)->where('role', self::OWNER)->select('stage_request_id'))->exists()
            || DB::table('stage_task_staff')->where('task_id', $task->id)->where('user_id', $user->id)->where('full_power', true)->exists();
    }

    /** Détail ; $viewer : stagiaire qui consulte (null : personnel). */
    public function detail(object $task, ?StageRequest $viewer, ?User $staff = null): array
    {
        $role = $viewer ? $this->role($task, $viewer) : null;
        $member = in_array($role, [self::OWNER, self::WORKER], true);
        $manages = $staff !== null && $this->manages($task, $staff);
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
            'invited'     => $this->invited($task),
            'mine'        => $member,
            'my_role'     => $role,
            'can_submit'  => $member && $task->status !== 'validated',
            'can_delete'  => $manages || ($role === self::OWNER && $task->created_by_type === 'intern' && (int) $task->version === 0),
            'can_review'  => $manages && $task->status === 'submitted',
            'can_publish' => $manages && $task->status === 'validated',
            // Inviter : le personnel qui a la main (qui règle aussi « compte pour lui » et le plein pouvoir), ou le stagiaire titulaire.
            'can_invite'  => $manages || $role === self::OWNER,
            'can_grant'   => $manages,
        ];
    }

    /** Invités de la tâche (stagiaires et personnel). */
    private function invited(object $task): array
    {
        $interns = DB::table('stage_task_members')->where('task_id', $task->id)->where('role', '!=', self::OWNER)->orderBy('id')->get();
        $people = StageRequest::whereIn('id', $interns->pluck('stage_request_id'))->get()->keyBy('id');
        $staff = DB::table('stage_task_staff as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.task_id', $task->id)
            ->orderBy('s.id')->get(['s.user_id', 's.full_power', 's.invited_by', 'u.first_name', 'u.last_name']);

        return [
            ...$interns->map(fn ($m) => [
                'type' => 'intern', 'id' => (int) $m->stage_request_id, 'name' => $people[$m->stage_request_id]?->fullName() ?? 'Stagiaire',
                'role' => $m->role, 'counts' => (bool) $m->counts, 'invited_by' => $m->invited_by,
            ])->all(),
            ...$staff->map(fn ($u) => [
                'type' => 'staff', 'id' => (int) $u->user_id, 'name' => trim($u->first_name . ' ' . $u->last_name),
                'full_power' => (bool) $u->full_power, 'invited_by' => $u->invited_by,
            ])->all(),
        ];
    }

    // ════════════════════════════════════════════════════════════════════
    // INVITATIONS
    // ════════════════════════════════════════════════════════════════════

    /**
     * Invite un stagiaire (pour voir, ou pour y travailler, en comptant ou
     * non pour lui) ou un membre du personnel (pour voir, ou avec plein
     * pouvoir). Le personnel qui a la main règle tout ; le stagiaire
     * titulaire invite pour voir ou pour travailler, sans que cela compte.
     * Une nouvelle invitation de la même personne met à jour ses droits.
     *
     * @param array{type: string, id: int, role?: ?string, counts?: ?bool, full_power?: ?bool} $who
     */
    public function invite(object $task, array $who, ?StageRequest $intern, ?User $staff): void
    {
        $grant = $this->assertCanInvite($task, $intern, $staff);
        $by = $staff ? trim($staff->first_name . ' ' . $staff->last_name) : $intern->fullName();

        if ($who['type'] === 'intern') {
            $req = StageRequest::where('id', $who['id'])->where('status', StageStatus::ACCEPTED)->first();
            if (!$req) {
                throw new BusinessException('Ce stagiaire n\'est plus en stage.', 'STAGE_TASK_INVITE_INTERN', 422);
            }
            $current = $this->role($task, $req);
            if ($current === self::OWNER) {
                throw new BusinessException('La tâche lui est déjà confiée.', 'STAGE_TASK_INVITE_OWNER', 409);
            }
            $role = ($who['role'] ?? null) === self::WORKER ? self::WORKER : self::VIEWER;
            $counts = $grant && $role === self::WORKER && !empty($who['counts']);
            DB::table('stage_task_members')->updateOrInsert(['task_id' => $task->id, 'stage_request_id' => $req->id],
                ['role' => $role, 'counts' => $counts, 'invited_by' => $current ? DB::raw('invited_by') : $by]);
            if (!$current) {
                $this->notifier->toDemandeur($req, 'Invitation sur une tâche', [
                    "{$by} vous invite sur une tâche, " . ($role === self::WORKER ? 'pour y travailler.' : 'pour la consulter.'),
                ], ['Tâche' => $task->title, 'Échéance' => $task->due_date ? $this->frDate($task->due_date) : null],
                    ctaLabel: 'Ouvrir la tâche', ctaUrl: $this->notifier->spaceUrl("/taches/{$task->id}"), context: 'stage-tache');
            }

            return;
        }

        $user = User::find($who['id']);
        if (!$user || !StageAccess::canManage($user)) {
            throw new BusinessException('Seul le personnel des stages peut être invité.', 'STAGE_TASK_INVITE_STAFF', 422);
        }
        $existing = DB::table('stage_task_staff')->where('task_id', $task->id)->where('user_id', $user->id)->first();
        DB::table('stage_task_staff')->updateOrInsert(['task_id' => $task->id, 'user_id' => $user->id], [
            'full_power' => $grant && !empty($who['full_power']), 'invited_by' => $existing?->invited_by ?? $by,
        ]);
        if (!$existing) {
            $this->notifier->toStaff(collect([$user]), 'Invitation sur une tâche', [
                "{$by} vous invite sur la tâche « {$task->title} »" . ($grant && !empty($who['full_power']) ? ', avec plein pouvoir (revue et publication).' : ', pour la suivre.'),
            ], ['Stagiaire(s)' => $this->members($task)->map->fullName()->implode(', ')], 'stage-tache', $this->staffUrl($task));
        }
    }

    /** Retire une invitation. */
    public function uninvite(object $task, string $type, int $id, ?StageRequest $intern, ?User $staff): void
    {
        $this->assertCanInvite($task, $intern, $staff);
        $type === 'intern'
            ? DB::table('stage_task_members')->where('task_id', $task->id)->where('stage_request_id', $id)->where('role', '!=', self::OWNER)->delete()
            : DB::table('stage_task_staff')->where('task_id', $task->id)->where('user_id', $id)->delete();
    }

    /** Peut inviter ? Renvoie vrai si l'auteur peut aussi accorder « compte pour lui » et le plein pouvoir. */
    private function assertCanInvite(object $task, ?StageRequest $intern, ?User $staff): bool
    {
        if ($staff && $this->manages($task, $staff)) {
            return true;
        }
        if ($intern && $this->role($task, $intern) === self::OWNER) {
            return false;
        }
        throw new BusinessException('Seuls le stagiaire titulaire et ses superviseurs peuvent inviter sur cette tâche.', 'STAGE_TASK_FORBIDDEN', 403);
    }

    /** Personnes qu'on peut inviter : stagiaires en stage (les siens d'abord pour un superviseur) et personnel des stages. */
    public function invitables(?StageRequest $intern, ?User $staff): array
    {
        $followed = $staff ? DB::table('stage_supervisions')->where('user_id', $staff->id)->pluck('stage_request_id')->flip() : collect();
        $interns = StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
            ->where('date_fin', '>=', BusinessHours::today()->toDateString())
            ->when($intern, fn ($q) => $q->where('id', '!=', $intern->id))
            ->orderBy('nom')->orderBy('prenoms')->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->fullName(), 'mine' => isset($followed[$r->id])])
            ->sortBy(fn ($r) => $r['mine'] ? 0 : 1)->values();
        $people = StageAccess::managers()->concat(StageAccess::supervisors())->unique('id')
            ->reject(fn ($u) => $staff && (int) $u->id === (int) $staff->id)
            ->map(fn ($u) => ['id' => (int) $u->id, 'name' => trim($u->first_name . ' ' . $u->last_name)])
            ->sortBy('name')->values();

        return ['interns' => $interns, 'staff' => $people];
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
            DB::table('stage_task_members')->insert($members->map(fn ($m) => ['task_id' => $id, 'stage_request_id' => $m->id, 'role' => self::OWNER])->all());

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

    /** Suppression : le personnel qui a la main, ou le stagiaire pour une tâche qu'il a créée et pas encore remise. */
    public function delete(object $task, ?StageRequest $intern, ?User $staff): void
    {
        if (!($staff && $this->manages($task, $staff))
            && !($intern && $this->role($task, $intern) === self::OWNER && $task->created_by_type === 'intern' && (int) $task->version === 0)) {
            throw new BusinessException('Vous ne pouvez supprimer que vos propres tâches, avant toute remise.', 'STAGE_TASK_FORBIDDEN', 403);
        }
        $paths = DB::table('stage_task_items')->where('task_id', $task->id)->whereNotNull('path')->pluck('path');
        DB::transaction(function () use ($task) {
            DB::table('stage_task_items')->where('task_id', $task->id)->delete();
            DB::table('stage_task_members')->where('task_id', $task->id)->delete();
            DB::table('stage_task_staff')->where('task_id', $task->id)->delete();
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
        if (!$this->canWork($task, $by)) {
            throw new BusinessException('Vous êtes invité pour consulter cette tâche, pas pour la remettre.', 'STAGE_TASK_FORBIDDEN', 403);
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
            $by->fullName() . ' a remis le travail' . ($task->version > 1 ? ' (nouvelle version).' : '.'),
        ], ['Tâche' => $task->title, 'Stagiaire(s)' => $members->map->fullName()->implode(', '), 'Version' => (string) $task->version,
            'Message' => $task->message], 'stage-tache', $this->staffUrl($task), $this->invitedStaff($task));

        return $task;
    }

    /** Revue : « validated » ou « revision » (à revoir, avec un commentaire). */
    public function review(object $task, User $by, string $decision, ?string $comment): object
    {
        $this->assertManages($task, $by);
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
        foreach ($this->members($task, true) as $m) {
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
        $this->assertManages($task, $by);
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
            foreach ($this->members($t, true) as $m) {
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
        // Auteurs : stagiaire(s) à qui la tâche est confiée, et invités pour qui le travail compte.
        $members = DB::table('stage_task_members')->whereIn('task_id', $ids)->where('counts', true)->whereIn('role', [self::OWNER, self::WORKER])
            ->orderByRaw("role = 'worker'")->orderBy('id')->get()->groupBy('task_id');
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

    /**
     * Auteurs de la tâche ; $workers : tous ceux qui y travaillent (invités
     * pour travailler compris, même si le travail ne compte pas pour eux).
     *
     * @return Collection<int, StageRequest>
     */
    private function members(object $task, bool $workers = false): Collection
    {
        $q = DB::table('stage_task_members')->where('task_id', $task->id)->whereIn('role', [self::OWNER, self::WORKER]);
        if (!$workers) {
            $q->where('counts', true);
        }

        return StageRequest::whereIn('id', $q->pluck('stage_request_id'))->get();
    }

    /** Personnel invité sur la tâche (comptes actifs). */
    private function invitedStaff(object $task): Collection
    {
        return DB::table('stage_task_staff as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.task_id', $task->id)
            ->whereNull('u.deleted_at')->get(['u.id', 'u.email', 'u.phone', 'u.first_name', 'u.last_name']);
    }

    private function assertManages(object $task, User $user): void
    {
        if (!$this->manages($task, $user)) {
            throw new BusinessException('Vous suivez cette tâche sans pouvoir la valider : seuls ses superviseurs le peuvent.', 'STAGE_TASK_FORBIDDEN', 403);
        }
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

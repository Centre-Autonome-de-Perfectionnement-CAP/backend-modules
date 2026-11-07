<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Core\Support\FilePreview;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\TaskService;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Espace tâches, partagé par le progiciel (personnel) et l'espace stagiaire :
 * le stagiaire est reconnu par sa session (attribut « intern »), le personnel
 * par son compte.
 */
class StageTaskController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TaskService $tasks) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'todo', 'submitted', 'revision', 'validated'])],
            'mine'   => ['nullable', 'boolean'],
        ]);

        return $this->guard(function () use ($request, $data) {
            if ($intern = $this->intern($request)) {
                return ['tasks' => $this->tasks->forIntern($intern), 'binome' => $this->partner($intern)?->fullName()];
            }

            return [
                'tasks'   => $this->tasks->forStaff(!empty($data['mine']) ? $request->user() : null, $data['status'] ?? 'all'),
                // Stagiaires en cours, pour confier une tâche
                'interns' => StageRequest::where('status', StageStatus::ACCEPTED)->whereNotNull('activated_at')
                    ->where('date_fin', '>=', BusinessHours::today()->toDateString())->orderBy('nom')->orderBy('prenoms')->get()
                    ->map(fn ($r) => ['id' => $r->id, 'name' => $r->fullName(), 'binome' => $this->partner($r)?->fullName()])->values(),
            ];
        });
    }

    public function published(): JsonResponse
    {
        return $this->guard(fn () => $this->tasks->published());
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($request, $id) {
            $task = $this->visible($request, $id);

            return $this->tasks->detail($task, $this->intern($request), $this->intern($request) ? null : $request->user());
        });
    }

    public function store(Request $request): JsonResponse
    {
        $intern = $this->intern($request);
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:200'],
            'instructions'     => ['nullable', 'string', 'max:3000'],
            'due_date'         => ['nullable', 'date_format:Y-m-d'],
            'pour_binome'      => ['nullable', 'boolean'],
            'stage_request_id' => [$intern ? 'prohibited' : 'required', 'integer'],
        ], ['title.required' => 'Donnez un titre à la tâche.']);

        return $this->guard(function () use ($request, $data, $intern) {
            $for = $intern ?? StageRequest::find($data['stage_request_id']);
            if (!$for) {
                throw new BusinessException('Stagiaire introuvable.', 'STAGE_TASK_INTERN', 404);
            }
            $task = $this->tasks->create($for, $data['title'], $data['instructions'] ?? null, $data['due_date'] ?? null,
                (bool) ($data['pour_binome'] ?? false), $intern ? null : $request->user());

            return $this->tasks->detail($task, $intern, $intern ? null : $request->user());
        }, 'Tâche créée.', 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($request, $id) {
            $intern = $this->intern($request);
            $this->tasks->delete($this->visible($request, $id), $intern, $intern ? null : $request->user());

            return null;
        }, 'Tâche supprimée.');
    }

    /** Remise du travail (espace stagiaire). */
    public function submit(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'files'   => ['nullable', 'array', 'max:' . TaskService::MAX_ITEMS],
            'files.*' => ['file', 'max:' . TaskService::MAX_FILE_KB, 'mimes:' . implode(',', TaskService::FILE_TYPES),
                'mimetypes:' . implode(',', TaskService::FILE_MIMES)],
            'links'   => ['nullable', 'array', 'max:' . TaskService::MAX_ITEMS],
            'links.*' => ['nullable', 'string', 'max:1000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'files.max'         => 'Cinq fichiers au plus.',
            'files.*.max'       => 'Un fichier dépasse 20 Mo.',
            'files.*.mimes'     => 'Fichiers acceptés : Word, PDF, PowerPoint, Excel, ZIP et images.',
            'files.*.mimetypes' => 'Fichiers acceptés : Word, PDF, PowerPoint, Excel, ZIP et images.',
        ]);

        return $this->guard(function () use ($request, $id, $data) {
            $intern = $this->intern($request) ?? abort(403);
            $task = $this->tasks->submit($this->visible($request, $id), $intern, $request->file('files', []),
                $data['links'] ?? [], $data['message'] ?? null);

            return $this->tasks->detail($task, $intern);
        }, 'Travail remis. Votre superviseur en est informé.');
    }

    public function decide(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['validated', 'revision'])],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->guard(fn () => $this->tasks->detail(
            $this->tasks->review($this->tasks->find($id), $request->user(), $data['decision'], $data['comment'] ?? null), null, $request->user(),
        ), $data['decision'] === 'validated' ? 'Travail validé.' : 'Demande de revoir envoyée.');
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['public' => ['required', 'boolean']]);

        return $this->guard(fn () => $this->tasks->detail(
            $this->tasks->publish($this->tasks->find($id), $request->user(), (bool) $data['public']), null, $request->user(),
        ), $data['public'] ? 'Travail rendu public : il sera annoncé ce soir.' : 'Travail retiré des travaux publiés.');
    }

    /** Fichier rendu, en JSON (entier ou par tranches) pour le visualiseur. */
    public function file(Request $request, int $id, int $item): JsonResponse
    {
        try {
            [$path, $name, $mime] = $this->tasks->file($this->visible($request, $id), $item);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return response()->json(FilePreview::payload($request, $this->tasks->disk(), $path, $name, $mime))
            ->header('Cache-Control', 'private, no-store');
    }

    // ── Outils ────────────────────────────────────────────────────────────

    private function intern(Request $request): ?StageRequest
    {
        $account = $request->attributes->get('intern');

        return $account instanceof StageInternAccount ? $account->stage : null;
    }

    /** Tâche visible : tout pour le personnel ; la sienne ou un travail publié pour un stagiaire. */
    private function visible(Request $request, int $id): object
    {
        $task = $this->tasks->find($id);
        $intern = $this->intern($request);
        if ($intern && !$this->tasks->internCanSee($task, $intern)) {
            throw new BusinessException('Cette tâche n\'existe plus.', 'STAGE_TASK_NOT_FOUND', 404);
        }

        return $task;
    }

    private function partner(StageRequest $req): ?StageRequest
    {
        return $req->team_id
            ? StageRequest::where('team_id', $req->team_id)->where('id', '!=', $req->id)->where('status', StageStatus::ACCEPTED)->first()
            : null;
    }

    private function guard(callable $fn, ?string $message = null, int $status = 200): JsonResponse
    {
        try {
            return $this->successResponse($fn(), $message ?? 'Opération réussie', $status);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }
    }
}

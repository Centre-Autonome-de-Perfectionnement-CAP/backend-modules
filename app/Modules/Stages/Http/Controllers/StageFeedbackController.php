<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Core\Support\FilePreview;
use App\Modules\Stages\Models\StageInternAccount;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Services\EvaluationService;
use App\Modules\Stages\Services\FeedbackService;
use App\Modules\Stages\Services\StageDocumentService;
use App\Modules\Stages\Support\StageAccess;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Documents et références, avis des stagiaires, notation du stage et
 * évaluation des stagiaires. Partagé par le progiciel (personnel) et
 * l'espace stagiaire (attribut « intern » de la session).
 */
class StageFeedbackController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly StageDocumentService $documents,
        private readonly FeedbackService $feedback,
        private readonly EvaluationService $evaluations,
    ) {}

    // ── Documents ─────────────────────────────────────────────────────────

    public function documents(): JsonResponse
    {
        return $this->guard(fn () => ['items' => $this->documents->list(), 'categories' => StageDocumentService::CATEGORIES]);
    }

    public function documentStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'category'    => ['required', Rule::in(array_keys(StageDocumentService::CATEGORIES))],
            'url'         => ['nullable', 'string', 'max:1000', 'required_without:file'],
            'file'        => ['nullable', 'file', 'max:' . StageDocumentService::MAX_FILE_KB,
                'mimes:' . implode(',', \App\Modules\Stages\Services\TaskService::FILE_TYPES)],
        ], [
            'title.required'       => 'Donnez un titre au document.',
            'url.required_without' => 'Ajoutez un fichier ou un lien.',
            'file.max'             => 'Le fichier dépasse 20 Mo.',
            'file.mimes'           => 'Fichiers acceptés : Word, PDF, PowerPoint, Excel, ZIP et images.',
        ]);

        return $this->guard(fn () => ['id' => $this->documents->create($data, $request->file('file'), $request->user())], 'Document ajouté : il sera annoncé ce soir.', 201);
    }

    public function documentDestroy(int $id): JsonResponse
    {
        return $this->guard(fn () => $this->documents->delete($id), 'Document supprimé.');
    }

    public function documentFile(Request $request, int $id): JsonResponse
    {
        try {
            [$path, $name, $mime] = $this->documents->file($id);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return response()->json(FilePreview::payload($request, $this->documents->disk(), $path, $name, $mime))->header('Cache-Control', 'private, no-store');
    }

    // ── Avis ──────────────────────────────────────────────────────────────

    /** Espace stagiaire : envoi d'un avis (anonyme par défaut). */
    public function feedbackStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'      => ['required', Rule::in(array_keys(FeedbackService::TYPES))],
            'subject'   => ['required', Rule::in(array_keys(FeedbackService::SUBJECTS))],
            'message'   => ['required', 'string', 'min:20', 'max:2000'],
            'signed'    => ['nullable', 'boolean'],
            'public_ok' => ['nullable', 'boolean'],
            'file'      => ['nullable', 'file', 'max:' . FeedbackService::MAX_FILE_KB, 'mimes:' . implode(',', FeedbackService::FILE_TYPES)],
        ], [
            'message.required' => 'Écrivez votre message.',
            'message.min'      => 'Votre message est trop court (20 caractères au moins).',
            'file.max'         => 'Le fichier dépasse 5 Mo.',
            'file.mimes'       => 'Fichiers acceptés : photo ou PDF.',
        ]);

        return $this->guard(fn () => $this->feedback->send($this->intern($request) ?? abort(403), $data, $request->file('file')),
            'Merci ! Votre avis a bien été envoyé.', 201);
    }

    public function feedbackIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'    => ['nullable', Rule::in(array_keys(FeedbackService::TYPES))],
            'subject' => ['nullable', Rule::in(array_keys(FeedbackService::SUBJECTS))],
        ]);

        return $this->guard(fn () => $this->feedback->staffList($request->user(), $data['type'] ?? null, $data['subject'] ?? null));
    }

    public function feedbackCount(Request $request): JsonResponse
    {
        return $this->guard(fn () => ['unread' => $this->feedback->unreadCount($request->user())]);
    }

    public function feedbackPublish(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['public' => ['required', 'boolean']]);

        return $this->guard(function () use ($request, $id, $data) {
            $this->lead($request);
            $this->feedback->publish($id, (bool) $data['public']);

            return null;
        }, $data['public'] ? 'Avis affiché sur la page publique.' : 'Avis retiré de la page publique.');
    }

    public function feedbackDestroy(Request $request, int $id): JsonResponse
    {
        return $this->guard(function () use ($request, $id) {
            $this->lead($request);
            $this->feedback->delete($id);

            return null;
        }, 'Avis supprimé.');
    }

    public function feedbackFile(Request $request, int $id): JsonResponse
    {
        try {
            [$path, $name, $mime] = $this->feedback->file($request->user(), $id);
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return response()->json(FilePreview::payload($request, $this->documents->disk(), $path, $name, $mime))->header('Cache-Control', 'private, no-store');
    }

    // ── Notation du stage ─────────────────────────────────────────────────

    /** Stagiaire : périodes à noter ; responsable : tableau des notes. */
    public function ratings(Request $request): JsonResponse
    {
        return $this->guard(function () use ($request) {
            if ($intern = $this->intern($request)) {
                return $this->feedback->ratingState($intern);
            }
            $this->lead($request);

            return $this->feedback->ratingsBoard();
        });
    }

    public function rate(Request $request): JsonResponse
    {
        $rule = ['required', 'integer', 'between:1,5'];
        $data = $request->validate([
            'period'        => ['required', 'string', 'regex:/^(\d{4}-\d{2}|fin)$/'],
            'scores'        => ['required', 'array'],
            ...collect(FeedbackService::CRITERIA)->keys()->mapWithKeys(fn ($k) => ["scores.{$k}" => $rule])->all(),
            'supervisors'   => ['nullable', 'array', 'max:5'],
            'supervisors.*' => $rule,
            'comment'       => ['nullable', 'string', 'max:1000'],
        ], ['scores.*.required' => 'Donnez une note à chaque critère.']);

        return $this->guard(fn () => $this->feedback->rate($this->intern($request) ?? abort(403), $data), 'Merci ! Votre note a bien été enregistrée.', 201);
    }

    /** Page publique : « Ce qu'en disent nos stagiaires ». */
    public function publicSummary(): JsonResponse
    {
        return $this->successResponse($this->feedback->publicSummary());
    }

    // ── Évaluation des stagiaires ─────────────────────────────────────────

    public function evaluations(Request $request): JsonResponse
    {
        return $this->guard(fn () => $this->evaluations->toRate($request->user()) + ['lead' => StageAccess::isLead($request->user())]);
    }

    public function evaluationSave(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'scores'   => ['required', 'array'],
            'scores.*' => ['required', 'numeric', 'min:0', 'max:20'],
            'comment'  => ['required', 'string', 'min:10', 'max:2000'],
        ], ['comment.required' => 'Le commentaire est obligatoire.', 'comment.min' => 'Le commentaire est trop court (10 caractères au moins).']);

        return $this->guard(fn () => $this->evaluations->save($this->stage($id), $request->user(), $data['scores'], $data['comment']), 'Note enregistrée.');
    }

    public function evaluationBoard(Request $request): JsonResponse
    {
        return $this->guard(function () use ($request) {
            $this->lead($request);

            return $this->evaluations->board();
        });
    }

    public function evaluationReturn(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer'], 'note' => ['required', 'string', 'min:5', 'max:1000']],
            ['note.required' => 'Dites ce qu\'il faut revoir.']);

        return $this->guard(function () use ($request, $id, $data) {
            $this->lead($request);
            $this->evaluations->returnTo($this->stage($id), (int) $data['user_id'], $data['note'], $request->user());

            return null;
        }, 'Note renvoyée au superviseur.');
    }

    public function evaluationValidate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['appreciation' => ['required', 'string', 'min:10', 'max:2000']],
            ['appreciation.required' => 'Écrivez l\'appréciation générale.']);

        return $this->guard(function () use ($request, $id, $data) {
            $this->lead($request);

            return $this->evaluations->validate($this->stage($id), $data['appreciation'], $request->user());
        }, 'Note validée : le stagiaire reçoit son bulletin.');
    }

    /** Bulletin PDF : le stagiaire pour le sien, le responsable pour tous. */
    public function evaluationPdf(Request $request, ?int $id = null): JsonResponse
    {
        try {
            $intern = $this->intern($request);
            if (!$intern) {
                $this->lead($request);
            }
            $file = $this->evaluations->bulletin($intern ?? $this->stage((int) $id));
            if (!$file) {
                throw new BusinessException('Le bulletin n\'est pas encore disponible.', 'STAGE_EVAL_PDF', 404);
            }
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return response()->json(FilePreview::payload($request, Storage::disk($file['disk']), $file['path'], $file['name'], 'application/pdf'))
            ->header('Cache-Control', 'private, no-store');
    }

    /** Espace stagiaire : sa note validée. */
    public function myEvaluation(Request $request): JsonResponse
    {
        return $this->guard(fn () => $this->evaluations->forIntern($this->intern($request) ?? abort(403)));
    }

    // ── Outils ────────────────────────────────────────────────────────────

    private function intern(Request $request): ?StageRequest
    {
        $account = $request->attributes->get('intern');

        return $account instanceof StageInternAccount ? $account->stage : null;
    }

    private function lead(Request $request): void
    {
        if (!StageAccess::isLead($request->user())) {
            throw new BusinessException('Réservé aux responsables.', 'STAGE_LEAD_ONLY', 403);
        }
    }

    private function stage(int $id): StageRequest
    {
        return StageRequest::find($id) ?? throw new BusinessException('Stagiaire introuvable.', 'STAGE_NOT_FOUND', 404);
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

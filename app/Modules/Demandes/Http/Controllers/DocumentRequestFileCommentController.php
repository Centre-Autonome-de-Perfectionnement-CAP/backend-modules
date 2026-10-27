<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Demandes\Services\FileCommentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Commentaires des acteurs sur les pièces d'un dossier.
 * Accès : gate demandes.view (posé sur les routes).
 */
class DocumentRequestFileCommentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FileCommentService $service) {}

    public function index(Request $request, int $id): JsonResponse
    {
        return $this->successResponse($this->service->list($id, $request->user()));
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'file_source' => ['required', 'string', Rule::in(FileCommentService::SOURCES)],
            'file_key'    => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'body'        => ['required', 'string', 'max:2000'],
        ]);

        if (trim(strip_tags($data['body'])) === '') {
            return $this->errorResponse('Le commentaire est vide.', 422, 'EMPTY_COMMENT');
        }

        try {
            $comment = $this->service->create($id, $data['file_source'], $data['file_key'], $data['body'], $request->user());
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        return $this->successResponse($comment, 'Commentaire ajouté.', 201);
    }

    public function update(Request $request, int $id, int $commentId): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        if (trim(strip_tags($data['body'])) === '') {
            return $this->errorResponse('Le commentaire est vide.', 422, 'EMPTY_COMMENT');
        }

        try {
            $comment = $this->service->update($id, $commentId, $data['body'], $request->user());
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        return $this->successResponse($comment, 'Commentaire modifié.');
    }

    public function destroy(Request $request, int $id, int $commentId): JsonResponse
    {
        try {
            $this->service->delete($id, $commentId, $request->user());
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }

        return $this->successResponse(null, 'Commentaire supprimé.');
    }
}

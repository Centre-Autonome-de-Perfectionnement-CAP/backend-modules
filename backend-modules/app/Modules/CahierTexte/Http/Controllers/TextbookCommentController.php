<?php

namespace App\Modules\CahierTexte\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CahierTexte\Http\Requests\CreateTextbookCommentRequest;
use App\Modules\CahierTexte\Http\Resources\TextbookCommentResource;
use App\Modules\CahierTexte\Services\TextbookCommentService;
use Illuminate\Http\Request;

class TextbookCommentController extends Controller
{
    protected $commentService;

    public function __construct(TextbookCommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Liste des commentaires d'une entrée
     */
    public function index($entryId)
    {
        try {
            $comments = $this->commentService->getByEntry($entryId);

            return response()->json([
                'success' => true,
                'data' => TextbookCommentResource::collection($comments),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commentaires',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau commentaire
     */
    public function store(CreateTextbookCommentRequest $request, $entryId)
    {
        try {
            $comment = $this->commentService->create(
                $entryId,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Commentaire créé avec succès',
                'data' => new TextbookCommentResource($comment),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du commentaire',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un commentaire
     */
    public function update(Request $request, $entryId, $commentId)
    {
        try {
            $request->validate([
                'comment' => 'required|string',
            ]);

            $comment = $this->commentService->update($commentId, $request->only('comment'));

            return response()->json([
                'success' => true,
                'message' => 'Commentaire mis à jour avec succès',
                'data' => new TextbookCommentResource($comment),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du commentaire',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un commentaire
     */
    public function destroy($entryId, $commentId)
    {
        try {
            $this->commentService->delete($commentId);

            return response()->json([
                'success' => true,
                'message' => 'Commentaire supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du commentaire',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Modules\CahierTexte\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CahierTexte\Http\Requests\CreateTextbookEntryRequest;
use App\Modules\CahierTexte\Http\Requests\UpdateTextbookEntryRequest;
use App\Modules\CahierTexte\Http\Resources\TextbookEntryResource;
use App\Modules\CahierTexte\Services\TextbookEntryService;
use Illuminate\Http\Request;

class TextbookEntryController extends Controller
{
    protected $textbookEntryService;

    public function __construct(TextbookEntryService $textbookEntryService)
    {
        $this->textbookEntryService = $textbookEntryService;
    }

    /**
     * Liste des entrées du cahier de texte
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'search',
                'program_id',
                'class_group_id',
                'professor_id',
                'status',
                'start_date',
                'end_date',
                'per_page',
            ]);

            $entries = $this->textbookEntryService->getAll($filters);

            return response()->json([
                'success' => true,
                'data' => TextbookEntryResource::collection($entries->items()),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détails d'une entrée
     */
    public function show($id)
    {
        try {
            $entry = $this->textbookEntryService->getById($id);

            return response()->json([
                'success' => true,
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Entrée non trouvée',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Créer une nouvelle entrée
     */
    public function store(CreateTextbookEntryRequest $request)
    {
        try {
            $entry = $this->textbookEntryService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Entrée créée avec succès',
                'data' => new TextbookEntryResource($entry),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une entrée
     */
    public function update(UpdateTextbookEntryRequest $request, $id)
    {
        try {
            $entry = $this->textbookEntryService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Entrée mise à jour avec succès',
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une entrée
     */
    public function destroy($id)
    {
        try {
            $this->textbookEntryService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Entrée supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publier une entrée
     */
    public function publish($id)
    {
        try {
            $entry = $this->textbookEntryService->publish($id);

            return response()->json([
                'success' => true,
                'message' => 'Entrée publiée avec succès',
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la publication de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valider une entrée
     */
    public function validate(Request $request, $id)
    {
        try {
            $entry = $this->textbookEntryService->validate($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Entrée validée avec succès',
                'data' => new TextbookEntryResource($entry),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'entrée',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Entrées par groupe de classe
     */
    public function byClassGroup(Request $request, $classGroupId)
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'status', 'per_page']);
            $entries = $this->textbookEntryService->getByClassGroup($classGroupId, $filters);

            return response()->json([
                'success' => true,
                'data' => TextbookEntryResource::collection($entries->items()),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Entrées par professeur
     */
    public function byProfessor(Request $request, $professorId)
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'status', 'per_page']);
            $entries = $this->textbookEntryService->getByProfessor($professorId, $filters);

            return response()->json([
                'success' => true,
                'data' => TextbookEntryResource::collection($entries->items()),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des entrées',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiques
     */
    public function statistics(Request $request)
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'class_group_id',
                'professor_id',
            ]);

            $stats = $this->textbookEntryService->getStatistics($filters);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

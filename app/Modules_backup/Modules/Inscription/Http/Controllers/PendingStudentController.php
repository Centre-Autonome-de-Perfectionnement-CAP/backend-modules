<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Http\Requests\CreatePendingStudentRequest;
use App\Modules\Inscription\Http\Requests\SubmitDocumentsRequest;
use App\Modules\Inscription\Http\Requests\UpdatePendingStudentLevelRequest;
use App\Modules\Inscription\Http\Requests\RenamePieceRequest;
use App\Modules\Inscription\Http\Resources\PendingStudentResource;
use App\Modules\Inscription\Services\PendingStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

/**
 * @OA\Tag(
 *     name="Pending Students",
 *     description="Gestion des étudiants en attente d'inscription"
 * )
 */

class PendingStudentController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected PendingStudentService $pendingStudentService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/pending-students",
     *     summary="Liste des étudiants en attente",
     *     description="Récupère la liste paginée des étudiants en attente avec possibilité de filtrage",
     *     operationId="getPendingStudents",
     *     tags={"Pending Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrer par status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "documents_submitted", "approved", "rejected"})
     *     ),
     *     @OA\Parameter(
     *         name="entry_level_id",
     *         in="query",
     *         description="Filtrer par niveau d'entrée",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des étudiants récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PendingStudent")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request): JsonResponse
{
    $filters = $request->only(['status', 'department_id', 'academic_year_id', 'entry_diploma_id', 'level', 'cohort', 'search']);
    $perPage = $this->getPerPage($request);

    $pendingStudents = $this->pendingStudentService->getAll($filters, $perPage);
    $transformedData = PendingStudentResource::collection($pendingStudents->items());

    $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
        $transformedData,
        $pendingStudents->total(),
        $pendingStudents->perPage(),
        $pendingStudents->currentPage(),
        ['path' => $request->url()]
    );

    return $this->successPaginatedResponse(
        $paginator,
        'Étudiants en attente récupérés avec succès'
    );
}

    /**
     * @OA\Post(
     *     path="/api/pending-students",
     *     summary="Créer un étudiant en attente",
     *     description="Crée un nouvel étudiant en attente d'inscription",
     *     operationId="createPendingStudent",
     *     tags={"Pending Students"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "first_name", "last_name", "phone", "entry_level_id", "entry_diploma_id"},
     *             @OA\Property(property="email", type="string", format="email", example="student@example.com"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="entry_level_id", type="integer", example=1),
     *             @OA\Property(property="entry_diploma_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Étudiant créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Étudiant en attente créé avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/PendingStudent")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function store(CreatePendingStudentRequest $request): JsonResponse
    {
        $pendingStudent = $this->pendingStudentService->create($request->validated());

        return $this->createdResponse(
            new PendingStudentResource($pendingStudent),
            'Étudiant en attente créé avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/pending-students/{pendingStudent}",
     *     summary="Détails d'un étudiant en attente",
     *     description="Récupère les détails d'un étudiant en attente spécifique",
     *     operationId="getPendingStudent",
     *     tags={"Pending Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="pendingStudent",
     *         in="path",
     *         description="ID de l'étudiant en attente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'étudiant récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/PendingStudent")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Étudiant non trouvé")
     * )
     */
    public function show(PendingStudent $pendingStudent): JsonResponse
    {
        return $this->successResponse(
            new PendingStudentResource($pendingStudent->load(['entryDiploma', 'studentPendingStudents.student'])),
            'Étudiant en attente récupéré avec succès'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/pending-students/{pendingStudent}",
     *     summary="Mettre à jour un étudiant en attente",
     *     description="Met à jour les informations d'un étudiant en attente",
     *     operationId="updatePendingStudent",
     *     tags={"Pending Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="pendingStudent",
     *         in="path",
     *         description="ID de l'étudiant en attente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="student@example.com"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="academic_year_id", type="integer", example=1),
     *             @OA\Property(property="level", type="string", example="L1"),
     *             @OA\Property(property="entry_diploma_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected", "withdrawn"}, example="approved"),
     *             @OA\Property(property="sponsorise", type="string", enum={"Oui", "Non"}, example="Non")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Étudiant mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Étudiant en attente mis à jour avec succès."),
     *             @OA\Property(property="data", ref="#/components/schemas/PendingStudent")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Étudiant non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function update(CreatePendingStudentRequest $request, PendingStudent $pendingStudent): JsonResponse
    {
        $data = $request->validated();

        \Log::info('=== UPDATE PENDING STUDENT START ===', [
            'pending_student_id' => $pendingStudent->id,
            'data_received' => $data,
            'current_status' => $pendingStudent->status,
            'current_cuca_opinion' => $pendingStudent->cuca_opinion,
            'current_cuo_opinion' => $pendingStudent->cuo_opinion,
        ]);

        // Si on met à jour les opinions CUCA ou CUO, ne pas changer le status automatiquement
        $isOpinionUpdate = isset($data['cuca_opinion']) || isset($data['cuo_opinion']);

        \Log::info('Opinion update check', [
            'isOpinionUpdate' => $isOpinionUpdate,
            'has_cuca_opinion' => isset($data['cuca_opinion']),
            'has_cuo_opinion' => isset($data['cuo_opinion']),
            'has_status' => isset($data['status']),
        ]);

        if (isset($data['status']) && $data['status'] !== $pendingStudent->status && !$isOpinionUpdate) {
            \Log::info('Calling changestatus', [
                'old_status' => $pendingStudent->status,
                'new_status' => $data['status'],
            ]);
            $pendingStudent = $this->pendingStudentService->changestatus($pendingStudent, $data['status']);
            unset($data['status']);
        }

        if (!empty($data)) {
            \Log::info('Calling update service', ['data' => $data]);
            $pendingStudent = $this->pendingStudentService->update($pendingStudent, $data);
        }

        \Log::info('=== UPDATE PENDING STUDENT END ===');

        return $this->updatedResponse(
            new PendingStudentResource($pendingStudent),
            'Étudiant en attente mis à jour avec succès'
        );
    }

    /**
 * Mettre à jour uniquement les statuss (exonéré, sponsorisé)
 */
public function updatestatus(Request $request, PendingStudent $pendingStudent): JsonResponse
{
    $validated = $request->validate([
        'exonere' => 'sometimes|in:Oui,Non',
        'sponsorise' => 'sometimes|in:Oui,Non',
    ]);

    $pendingStudent->update($validated);

    return $this->successResponse(
        new PendingStudentResource($pendingStudent),
        'statuss mis à jour avec succès'
    );
}

    /**
     * Mettre à jour le niveau d'études
     */
    public function updateLevel(UpdatePendingStudentLevelRequest $request, PendingStudent $pendingStudent): JsonResponse
    {
        $pendingStudent->update($request->validated());

        return $this->successResponse(
            new PendingStudentResource($pendingStudent),
            'Niveau d\'études mis à jour avec succès'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/pending-students/{pendingStudent}",
     *     summary="Supprimer un étudiant en attente",
     *     description="Supprime un étudiant en attente",
     *     operationId="deletePendingStudent",
     *     tags={"Pending Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="pendingStudent",
     *         in="path",
     *         description="ID de l'étudiant en attente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Étudiant supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Étudiant en attente supprimé avec succès.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Étudiant non trouvé"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function destroy(PendingStudent $pendingStudent): JsonResponse
    {
        $this->pendingStudentService->delete($pendingStudent);

        return $this->deletedResponse('Étudiant en attente supprimé avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/pending-students/{pendingStudent}/documents",
     *     summary="Soumettre des documents",
     *     description="Soumet des documents pour un étudiant en attente (accessible sans authentification)",
     *     operationId="submitDocuments",
     *     tags={"Pending Students"},
     *     @OA\Parameter(
     *         name="pendingStudent",
     *         in="path",
     *         description="ID de l'étudiant en attente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"documents", "document_types"},
     *                 @OA\Property(
     *                     property="documents",
     *                     type="array",
     *                     description="Fichiers à uploader",
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *                 @OA\Property(
     *                     property="document_types",
     *                     type="array",
     *                     description="Types des documents correspondants",
     *                     @OA\Items(type="string", example="diploma")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Documents soumis avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Documents soumis avec succès."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pending_student", ref="#/components/schemas/PendingStudent"),
     *                 @OA\Property(property="uploaded_files", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="original_name", type="string"),
     *                     @OA\Property(property="size", type="integer"),
     *                     @OA\Property(property="mime_type", type="string")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Étudiant non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function submitDocuments(SubmitDocumentsRequest $request, PendingStudent $pendingStudent): JsonResponse
    {
        $result = $this->pendingStudentService->submitDocuments(
            $pendingStudent,
            $request->file('documents'),
            $request->document_types,
            auth()->id()
        );

        return $this->successResponse($result, 'Documents soumis avec succès');
    }

    /**
     * Renommer une pièce pour un étudiant
     */
    public function renamePiece(RenamePieceRequest $request, PendingStudent $pendingStudent): JsonResponse
    {
        $validated = $request->validated();

        $documents = is_string($pendingStudent->documents)
            ? json_decode($pendingStudent->documents, true)
            : ($pendingStudent->documents ?? []);

        if (!isset($documents[$validated['piece_key']])) {
            return $this->errorResponse('Pièce non trouvée', 404, 'PIECE_NOT_FOUND');
        }

        // Convertir en format objet si c'est une string
        $currentValue = $documents[$validated['piece_key']];
        if (is_string($currentValue)) {
            $documents[$validated['piece_key']] = [
                'url' => $currentValue,
                'custom_name' => $validated['custom_name']
            ];
        } else {
            $documents[$validated['piece_key']] = array_merge(
                is_array($currentValue) ? $currentValue : [],
                ['custom_name' => $validated['custom_name']]
            );
        }

        $pendingStudent->update(['documents' => $documents]);

        return $this->successResponse(
            new PendingStudentResource($pendingStudent),
            'Nom de la pièce mis à jour avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/pending-students/{pendingStudent}/documents",
     *     summary="Récupérer les documents",
     *     description="Récupère la liste des documents soumis par un étudiant en attente",
     *     operationId="getPendingStudentDocuments",
     *     tags={"Pending Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="pendingStudent",
     *         in="path",
     *         description="ID de l'étudiant en attente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Documents récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"), description="Liste des fichiers")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Étudiant non trouvé")
     * )
     */
    public function getDocuments(Request $request, PendingStudent $pendingStudent): JsonResponse
    {
        // Vérifier que l'utilisateur est authentifié pour récupérer les documents
        if (!auth()->check()) {
            return $this->errorResponse(
                'Authentification requise pour récupérer les documents',
                'AUTH_REQUIRED',
                401
            );
        }

        $files = $this->pendingStudentService->getDocuments($pendingStudent, auth()->id());

        return $this->successResponse($files, 'Documents récupérés avec succès');
    }
}

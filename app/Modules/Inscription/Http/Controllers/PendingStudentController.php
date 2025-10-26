<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Http\Requests\CreatePendingStudentRequest;
use App\Modules\Inscription\Http\Resources\PendingStudentResource;
use App\Modules\Inscription\Services\PendingStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @OA\Tag(
 *     name="Pending Students",
 *     description="Gestion des étudiants en attente d'inscription"
 * )
 */

class PendingStudentController extends Controller
{
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
     *         description="Filtrer par statut",
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
        try {
            $filters = $request->only(['status', 'entry_level_id', 'search']);
            $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
            
            $pendingStudents = $this->pendingStudentService->getAll($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => PendingStudentResource::collection($pendingStudents),
                'meta' => [
                    'total' => $pendingStudents->total(),
                    'per_page' => $pendingStudents->perPage(),
                    'current_page' => $pendingStudents->currentPage(),
                    'last_page' => $pendingStudents->lastPage(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur récupération étudiants en attente', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
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
        try {
            $pendingStudent = $this->pendingStudentService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Étudiant en attente créé avec succès.',
                'data' => new PendingStudentResource($pendingStudent),
            ], 201);
        } catch (Exception $e) {
            Log::error('Erreur création étudiant en attente', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
        return response()->json([
            'success' => true,
            'data' => new PendingStudentResource($pendingStudent->load(['entryLevel', 'entryDiploma', 'studentPendingStudents.student'])),
        ]);
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
     *             @OA\Property(property="entry_level_id", type="integer", example=1),
     *             @OA\Property(property="entry_diploma_id", type="integer", example=1)
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
        try {
            $pendingStudent = $this->pendingStudentService->update($pendingStudent, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Étudiant en attente mis à jour avec succès.',
                'data' => new PendingStudentResource($pendingStudent),
            ]);
        } catch (Exception $e) {
            Log::error('Erreur mise à jour étudiant', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
        try {
            $this->pendingStudentService->delete($pendingStudent);

            return response()->json([
                'success' => true,
                'message' => 'Étudiant en attente supprimé avec succès.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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
    public function submitDocuments(Request $request, PendingStudent $pendingStudent): JsonResponse
    {
        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'required|file|max:51200', // 50MB max par fichier
            'document_types' => 'required|array',
            'document_types.*' => 'required|string',
        ]);

        try {
            $uploadedFiles = [];

            DB::transaction(function () use ($request, $pendingStudent, &$uploadedFiles) {
                foreach ($request->file('documents') as $index => $file) {
                    $documentType = $request->document_types[$index] ?? 'unknown';

                    $uploadedFile = $this->fileStorageService->uploadFile(
                        $file,
                        auth()->id() ?? null, // Peut être null si non authentifié
                        'private',
                        'inscription_documents',
                        'inscription',
                        'pending_student',
                        $pendingStudent->id,
                        [
                            'document_type' => $documentType,
                            'submitted_by' => auth()->id() ?? null,
                        ]
                    );

                    $uploadedFiles[] = $uploadedFile;
                }

                // Mettre à jour le statut si nécessaire
                if ($pendingStudent->status === 'pending') {
                    $pendingStudent->update(['status' => 'documents_submitted']);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Documents soumis avec succès.',
                'data' => [
                    'pending_student' => new PendingStudentResource($pendingStudent->fresh()),
                    'uploaded_files' => collect($uploadedFiles)->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'original_name' => $file->original_name,
                            'size' => $file->size,
                            'mime_type' => $file->mime_type,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission des documents.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise pour récupérer les documents.',
            ], 401);
        }

        $files = $this->fileStorageService->getUserFiles(
            auth()->id(),
            [
                'module_name' => 'inscription',
                'module_resource_type' => 'pending_student',
                'module_resource_id' => $pendingStudent->id,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }
}

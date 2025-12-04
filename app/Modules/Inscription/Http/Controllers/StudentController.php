<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\StudentService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Students",
 *     description="Gestion des étudiants inscrits"
 * )
 */
class StudentController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected StudentService $studentService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/inscription/students",
     *     summary="Liste des étudiants inscrits",
     *     description="Récupère la liste paginée des étudiants inscrits avec possibilité de filtrage",
     *     operationId="getStudents",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filtrer par année académique",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filiere",
     *         in="query",
     *         description="Filtrer par filière",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="niveau",
     *         in="query",
     *         description="Filtrer par niveau d'études",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par nom, prénom ou matricule",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['year', 'filiere', 'entry_diploma', 'niveau', 'cohort', 'redoublant', 'search']);
        $perPage = $this->getPerPage($request);
        
        $students = $this->studentService->getAll($filters, $perPage);

        return $this->successResponse($students, 'Étudiants récupérés avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/inscription/students/{id}",
     *     summary="Détails d'un étudiant",
     *     description="Récupère les détails complets d'un étudiant par son ID",
     *     operationId="getStudentDetails",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de l'étudiant",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'étudiant récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Étudiant non trouvé"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $student = $this->studentService->getById($id);

        if (!$student) {
            return $this->errorResponse('Étudiant non trouvé', 404);
        }

        return $this->successResponse($student, 'Détails de l\'étudiant récupérés');
    }

    /**
     * @OA\Get(
     *     path="/api/inscription/students/export/fiche-presence",
     *     summary="Exporter la fiche de présence",
     *     description="Génère un PDF de la fiche de présence pour une classe donnée",
     *     operationId="exportFichePresence",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Année académique",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filiere",
     *         in="query",
     *         description="Filière",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="niveau",
     *         in="query",
     *         description="Niveau d'études",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF généré avec succès"
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function exportFichePresence(Request $request)
    {
        $request->validate([
            'cohort' => 'required|not_in:all',
        ], [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
            'cohort.not_in' => 'Veuillez sélectionner une cohorte spécifique',
        ]);
        
        $filters = $request->only(['year', 'filiere', 'niveau', 'cohort', 'groupe']);
        return $this->studentService->exportFichePresence($filters);
    }

    /**
     * @OA\Get(
     *     path="/api/inscription/students/export/fiche-emargement",
     *     summary="Exporter la fiche d'émargement",
     *     description="Génère un PDF de la fiche d'émargement pour une classe donnée",
     *     operationId="exportFicheEmargement",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Année académique",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filiere",
     *         in="query",
     *         description="Filière",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="niveau",
     *         in="query",
     *         description="Niveau d'études",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF généré avec succès"
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function exportFicheEmargement(Request $request)
    {
        $request->validate([
            'cohort' => 'required|not_in:all',
        ], [
            'cohort.required' => 'La sélection de la cohorte est obligatoire',
            'cohort.not_in' => 'Veuillez sélectionner une cohorte spécifique',
        ]);
        
        $filters = $request->only(['year', 'filiere', 'niveau', 'cohort', 'groupe']);
        return $this->studentService->exportFicheEmargement($filters);
    }

    /**
     * @OA\Put(
     *     path="/api/inscription/students/{id}",
     *     summary="Mettre à jour un étudiant",
     *     description="Met à jour les informations d'un étudiant",
     *     operationId="updateStudent",
     *     tags={"Students"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de l'étudiant",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="gender", type="string", enum={"M", "F"}),
     *             @OA\Property(property="date_of_birth", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Étudiant mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Étudiant non trouvé"),
     *     @OA\Response(response=422, description="Données invalides"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'sometimes|required|in:M,F,Masculin,Féminin',
            'date_of_birth' => 'nullable|date',
        ]);

        $updated = $this->studentService->update($id, $validated);

        if (!$updated) {
            return $this->errorResponse('Étudiant non trouvé ou mise à jour échouée', 404);
        }

        return $this->successResponse($updated, 'Étudiant mis à jour avec succès');
    }
}

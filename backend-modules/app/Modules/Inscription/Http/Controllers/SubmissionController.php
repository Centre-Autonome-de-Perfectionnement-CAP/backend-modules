<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Models\ReclamationPeriod;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Services\AcademicYearService;
use App\Modules\Inscription\Http\Resources\SubmissionPeriodResource;
use App\Modules\Inscription\Http\Resources\ReclamationPeriodResource;
use App\Modules\Inscription\Http\Resources\AcademicYearResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Inscription\Http\Requests\CheckReclamationStatusRequest;
use Illuminate\Support\Str;
use App\Traits\ApiResponse;

/**
 * @OA\Tag(
 *     name="Submission Management",
 *     description="Gestion des périodes de soumission et réclamation"
 * )
 */
class SubmissionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AcademicYearService $academicYearService
    ) {
        $this->middleware('auth:sanctum')->except(['getAcademicYears', 'getAcademicYear']);
    }

    /**
     * @OA\Get(
     *     path="/api/submissions/active-periods",
     *     summary="Périodes de soumission actives",
     *     description="Récupère la liste des périodes de soumission actuellement actives",
     *     operationId="getActiveSubmissionPeriods",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Périodes récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/SubmissionPeriod"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function getActiveSubmissionPeriods(): JsonResponse
    {
        $activePeriods = $this->academicYearService->getActiveSubmissionPeriods();

        return $this->successResponse(
            SubmissionPeriodResource::collection($activePeriods),
            'Périodes de soumission actives récupérées avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/submissions/active-reclamation-periods",
     *     summary="Périodes de réclamation actives",
     *     description="Récupère la liste des périodes de réclamation actuellement actives",
     *     operationId="getActiveReclamationPeriods",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Périodes récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ReclamationPeriod"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function getActiveReclamationPeriods(): JsonResponse
    {
        $activePeriods = $this->academicYearService->getActiveReclamationPeriods();

        return $this->successResponse(
            ReclamationPeriodResource::collection($activePeriods),
            'Périodes de réclamation actives récupérées avec succès'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/submissions/check-status",
     *     summary="Vérifier le statut de soumission",
     *     description="Vérifie si la soumission est ouverte pour une année académique donnée",
     *     operationId="checkSubmissionStatus",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"academic_year_id"},
     *             @OA\Property(property="academic_year_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut vérifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_open", type="boolean", example=true),
     *                 @OA\Property(property="academic_year", ref="#/components/schemas/AcademicYear"),
     *                 @OA\Property(property="submission_period", ref="#/components/schemas/SubmissionPeriod"),
     *                 @OA\Property(property="current_time", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function checkSubmissionStatus(Request $request): JsonResponse
    {
        $statusData = $this->academicYearService->checkSubmissionStatus($request->academic_year_id);
        
        return $this->successResponse($statusData, 'Statut de soumission vérifié avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/submissions/check-reclamation-status",
     *     summary="Vérifier le statut de réclamation",
     *     description="Vérifie si la réclamation est ouverte pour une année académique donnée",
     *     operationId="checkReclamationStatus",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"academic_year_id"},
     *             @OA\Property(property="academic_year_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut vérifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_open", type="boolean", example=true),
     *                 @OA\Property(property="academic_year", ref="#/components/schemas/AcademicYear"),
     *                 @OA\Property(property="reclamation_period", ref="#/components/schemas/ReclamationPeriod"),
     *                 @OA\Property(property="current_time", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function checkReclamationStatus(CheckReclamationStatusRequest $request): JsonResponse
    {
        $statusData = $this->academicYearService->checkReclamationStatus($request->academic_year_id);
        
        return $this->successResponse($statusData, 'Statut de réclamation vérifié avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/academic-years",
     *     summary="Liste des années académiques",
     *     description="Récupère la liste de toutes les années académiques",
     *     operationId="getAcademicYears",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Années académiques récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AcademicYear"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function getAcademicYears(): JsonResponse
    {
        $academicYears = $this->academicYearService->getAllYears();

        return $this->successResponse(
            AcademicYearResource::collection($academicYears),
            'Années académiques récupérées avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/academic-years/{academicYear}",
     *     summary="Détails d'une année académique",
     *     description="Récupère les détails d'une année académique spécifique avec ses périodes",
     *     operationId="getAcademicYear",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="academicYear",
     *         in="path",
     *         description="ID de l'année académique",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Année académique récupérée avec succès",
     *         @OA\JsonContent(
     *     summary="Créer une période de soumission",
     *     description="Crée une nouvelle période de soumission pour une filière et une année académique",
     *     operationId="createSubmissionPeriod",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"academic_year_id","department_id","start_date","end_date"},
     *             @OA\Property(property="academic_year_id", type="integer", example=1),
     *             @OA\Property(property="department_id", type="integer", example=2),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-10-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Période créée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SubmissionPeriod")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     * Create a new submission period
     */
    public function store(Request $request): JsonResponse
    {
        $period = $this->academicYearService->createSubmissionPeriod($request->all());

        return $this->createdResponse(
            new SubmissionPeriodResource($period),
            'Période de soumission créée avec succès'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/submissions/{submissionPeriod}",
     *     summary="Mettre à jour une période de soumission",
     *     description="Met à jour une période de soumission existante",
     *     operationId="updateSubmissionPeriod",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="submissionPeriod",
     *         in="path",
     *         required=true,
     *         description="ID de la période de soumission",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="academic_year_id", type="integer"),
     *             @OA\Property(property="department_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Période mise à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/SubmissionPeriod")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Période non trouvée")
     * )
     * Update an existing submission period
     */
    public function update(Request $request, SubmissionPeriod $submissionPeriod): JsonResponse
    {
        // TODO: Créer UpdateSubmissionPeriodRequest
        $updatedPeriod = $this->academicYearService->updateSubmissionPeriod($submissionPeriod, $request->all());

        return $this->updatedResponse(
            new SubmissionPeriodResource($updatedPeriod),
            'Période de soumission mise à jour avec succès'
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/submissions/{submissionPeriod}",
     *     summary="Supprimer une période de soumission",
     *     description="Supprime une période de soumission",
     *     operationId="deleteSubmissionPeriod",
     *     tags={"Submission Management"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="submissionPeriod",
     *         in="path",
     *         required=true,
     *         description="ID de la période de soumission",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Période supprimée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=404, description="Période non trouvée")
     * )
     * Delete a submission period
     */
    public function destroy(SubmissionPeriod $submissionPeriod): JsonResponse
    {
        $this->academicYearService->deleteSubmissionPeriod($submissionPeriod);

        return $this->deletedResponse('Période de soumission supprimée avec succès');
    }
}

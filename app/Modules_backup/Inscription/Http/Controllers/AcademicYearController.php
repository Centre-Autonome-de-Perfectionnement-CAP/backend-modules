<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Services\AcademicYearService;
use App\Modules\Inscription\Http\Requests\StoreAcademicYearRequest;
use App\Modules\Inscription\Http\Requests\UpdateAcademicYearRequest;
use App\Modules\Inscription\Http\Requests\ManagePeriodsRequest;
use App\Modules\Inscription\Http\Requests\ExtendPeriodsRequest;
use App\Modules\Inscription\Http\Resources\AcademicYearResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Academic Years",
 *     description="Gestion des années académiques et périodes"
 * )
 */
class AcademicYearController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AcademicYearService $academicYearService
    ) {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * Liste des années académiques
     */
    public function index(): JsonResponse
    {
        $academicYears = $this->academicYearService->getAllYears();
        return $this->successResponse(
            AcademicYearResource::collection($academicYears),
            'Années académiques récupérées avec succès'
        );
    }

    /**
     * Détails d'une année académique
     */
    public function show(AcademicYear $academicYear): JsonResponse
    {
        return $this->successResponse(
            new AcademicYearResource($academicYear->load(['submissionPeriods'])),
            'Année académique récupérée avec succès'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/academic-years",
     *     summary="Créer une année académique",
     *     tags={"Academic Years"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"year_start","year_end","submission_start","submission_end"},
     *             @OA\Property(property="year_start", type="string", format="date"),
     *             @OA\Property(property="year_end", type="string", format="date"),
     *             @OA\Property(property="submission_start", type="string", format="date"),
     *             @OA\Property(property="submission_end", type="string", format="date"),
     *             @OA\Property(property="departments", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Créée"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function store(StoreAcademicYearRequest $request): JsonResponse
    {
        $year = $this->academicYearService->create($request->validated());
        return $this->createdResponse($year, 'Année académique créée avec succès');
    }

    /**
     * @OA\Put(
     *     path="/api/academic-years/{academicYear}",
     *     summary="Mettre à jour une année académique",
     *     tags={"Academic Years"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="academicYear", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="year_start", type="string", format="date"),
     *             @OA\Property(property="year_end", type="string", format="date"),
     *             @OA\Property(property="submission_start", type="string", format="date"),
     *             @OA\Property(property="submission_end", type="string", format="date"),
     *             @OA\Property(property="departments", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Mis à jour")
     * )
     */
    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $updated = $this->academicYearService->update($academicYear, $request->validated());
        return $this->updatedResponse($updated, 'Année académique mise à jour avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/api/academic-years/{academicYear}",
     *     summary="Supprimer une année académique",
     *     tags={"Academic Years"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="academicYear", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Supprimée")
     * )
     */
    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        $this->academicYearService->delete($academicYear);
        return $this->deletedResponse('Année académique supprimée avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/academic-years/{academicYear}/periods",
     *     summary="Ajouter des périodes pour des départements",
     *     tags={"Academic Years"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="academicYear", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"start_date","end_date","departments"},
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="departments", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Créées")
     * )
     */
    public function addPeriods(ManagePeriodsRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $this->academicYearService->addPeriods($academicYear, $request->validated());
        return $this->createdResponse(null, 'Périodes ajoutées avec succès');
    }

    /**
     * @OA\Put(
     *     path="/api/academic-years/{academicYear}/periods",
     *     summary="Étendre la date de fin pour des périodes",
     *     tags={"Academic Years"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="academicYear", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"start_date","old_end_date","new_end_date","departments"},
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="old_end_date", type="string", format="date"),
     *             @OA\Property(property="new_end_date", type="string", format="date"),
     *             @OA\Property(property="departments", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Mises à jour")
     * )
     */
    public function extendPeriods(ExtendPeriodsRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $updated = $this->academicYearService->extendPeriods($academicYear, $request->validated());
        return $this->updatedResponse(['updated_count' => $updated], 'Périodes étendues avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/academic-years/{academicYear}/periods",
     *     summary="Récupérer les périodes d'une année académique",
     *     tags={"Academic Years"},
     *     @OA\Parameter(name="academicYear", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Liste des périodes récupérée")
     * )
     */
    public function getPeriods(AcademicYear $academicYear): JsonResponse
    {
        $periods = $this->academicYearService->getPeriods($academicYear);
        return $this->successResponse($periods, 'Périodes récupérées avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/api/academic-years/{academicYear}/periods",
     *     summary="Supprimer des périodes (combinaison de dates)",
     *     tags={"Academic Years"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="academicYear", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"start_date","end_date","departments"},
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="departments", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Supprimées")
     * )
     */
    public function deletePeriods(ManagePeriodsRequest $request, AcademicYear $academicYear): JsonResponse
    {
        $deleted = $this->academicYearService->deletePeriods($academicYear, $request->validated());
        return $this->deletedResponse("Périodes supprimées avec succès ({$deleted} période(s))");
    }
}

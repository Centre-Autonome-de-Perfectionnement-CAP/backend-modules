<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\CycleService;
use App\Modules\Inscription\Http\Resources\CycleResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Cycles",
 *     description="Gestion des cycles d'études"
 * )
 */
class CycleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CycleService $cycleService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/cycles",
     *     summary="Liste des cycles avec leurs départements",
     *     description="Récupère la liste de tous les cycles avec leurs départements associés",
     *     operationId="getCyclesWithDepartments",
     *     tags={"Cycles"},
     *     @OA\Response(
     *         response=200,
     *         description="Cycles récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Cycle"))
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $cycles = $this->cycleService->getAllWithDepartments();
        return $this->successResponse(
            CycleResource::collection($cycles),
            'Cycles récupérés avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/filieres",
     *     summary="Tous les départements avec périodes de soumission (format Filiere)",
     *     description="Retourne tous les départements de tous les cycles avec périodes au format front: id, title, cycle, dateLimite, image, badge",
     *     operationId="getAllDepartmentsWithSubmissionPeriods",
     *     tags={"Cycles"},
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="abbreviation", type="string"),
     *                 @OA\Property(property="cycle", type="string", enum={"licence","master","ingenierie"}),
     *                 @OA\Property(property="dateLimite", type="string", nullable=true),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="badge", type="string", enum={"inscriptions-ouvertes","inscriptions-fermees","prochainement"}, nullable=true)
     *             ))
     *         )
     *     )
     * )
     */
    public function allDepartmentsWithPeriods(): JsonResponse
    {
        $filieres = $this->cycleService->getAllDepartmentsWithPeriods();
        return $this->successResponse(
            $filieres,
            'Départements récupérés avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/next-deadline",
     *     summary="Périodes d'inscription actives groupées par deadline",
     *     description="Retourne toutes les périodes d'inscription actives groupées par date de fin",
     *     operationId="getNextDeadline",
     *     tags={"Cycles"},
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", enum={"open","closed"}),
     *                 @OA\Property(property="periods", type="array", @OA\Items(
     *                     @OA\Property(property="deadline", type="string", format="date-time"),
     *                     @OA\Property(property="filieres", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="abbreviation", type="string"),
     *                         @OA\Property(property="cycle", type="string")
     *                     ))
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function nextDeadline(): JsonResponse
    {
        $data = $this->cycleService->getNextDeadline();
        return $this->successResponse(
            $data,
            'Deadlines récupérés avec succès'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/niveaux",
     *     summary="Niveaux d'études par filière",
     *     description="Retourne les niveaux d'études disponibles pour chaque département/filière",
     *     operationId="getStudyLevels",
     *     tags={"Cycles"},
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="value", type="string", example="L1"),
     *                         @OA\Property(property="label", type="string", example="Licence 1")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function studyLevels(): JsonResponse
    {
        $levels = $this->cycleService->getStudyLevelsByDepartment();
        return $this->successResponse(
            $levels,
            'Niveaux récupérés avec succès'
        );
    }

    /**
     * Tous les niveaux d'études (format plat)
     */
    public function allStudyLevels(): JsonResponse
    {
        $levels = $this->cycleService->getAllStudyLevels();
        return $this->successResponse(
            $levels,
            'Tous les niveaux récupérés avec succès'
        );
    }

    /**
     * Liste des cohortes disponibles
     */
    public function cohorts(): JsonResponse
    {
        $cohorts = \DB::table('academic_paths')
            ->distinct()
            ->whereNotNull('cohort')
            ->pluck('cohort')
            ->values();

        return $this->successResponse(
            $cohorts,
            'Cohortes récupérées avec succès'
        );
    }
}

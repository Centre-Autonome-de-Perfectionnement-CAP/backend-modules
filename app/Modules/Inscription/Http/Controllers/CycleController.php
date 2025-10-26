<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Http\Resources\CycleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @OA\Tag(
 *     name="Cycles",
 *     description="Gestion des cycles d'études"
 * )
 */
class CycleController extends Controller
{
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
        $cycles = Cycle::with('departments')->get();

        return response()->json([
            'success' => true,
            'data' => CycleResource::collection($cycles),
        ]);
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
        $departments = Department::with(['cycle', 'submissionPeriod'])->get();
        $today = Carbon::today();

        $filieres = $departments->map(function ($dept) use ($today) {
            $cycleName = strtolower($dept->cycle->name ?? '');
            if ($cycleName === 'ingénieur') {
                $cycleName = 'ingenierie';
            }

            // Trouver la période active ou la plus proche
            $activePeriod = null;
            $badge = null;
            $dateLimite = null;

            foreach ($dept->submissionPeriod as $p) {
                $start = Carbon::parse($p->start_date);
                $end = Carbon::parse($p->end_date);

                // Période en cours
                if ($today->between($start, $end)) {
                    $activePeriod = $p;
                    $badge = 'inscriptions-ouvertes';
                    $dateLimite = $end->format('Y-m-d');
                    break;
                }
                // Période future
                if ($start->gt($today)) {
                    if (!$activePeriod) {
                        $activePeriod = $p;
                        $badge = 'prochainement';
                        $dateLimite = $end->format('Y-m-d');
                    }
                }
            }

            // Si aucune période active/future, c'est fermé
            if (!$badge) {
                $badge = 'inscriptions-fermees';
            }

            return [
                'id' => $dept->id,
                'title' => $dept->name,
                'abbreviation' => $dept->abbreviation ?? '',
                'cycle' => $cycleName,
                'dateLimite' => $dateLimite,
                'image' => '', // À compléter selon votre logique (URL ou path)
                'badge' => $badge,
            ];
        });

        return response()->json(['success' => true, 'data' => $filieres->values()]);
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
        $today = Carbon::now();

        // Récupérer toutes les périodes actives (end_date >= aujourd'hui)
        $activePeriods = SubmissionPeriod::with(['department.cycle'])
            ->where('end_date', '>=', $today)
            ->orderBy('end_date', 'asc')
            ->get();

        if ($activePeriods->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'closed',
                    'periods' => []
                ]
            ]);
        }

        // Grouper par date de fin
        $groupedByDeadline = $activePeriods->groupBy(function ($period) {
            return Carbon::parse($period->end_date)->format('Y-m-d');
        });

        // Formater les données
        $periods = $groupedByDeadline->map(function ($periodsGroup, $dateKey) {
            $deadline = Carbon::parse($dateKey)->endOfDay();
            
            $filieres = $periodsGroup->map(function ($period) {
                $cycleName = strtolower($period->department->cycle->name ?? '');
                if ($cycleName === 'ingénieur') {
                    $cycleName = 'ingenierie';
                }

                return [
                    'id' => $period->department->id,
                    'name' => $period->department->name,
                    'abbreviation' => $period->department->abbreviation ?? '',
                    'cycle' => $cycleName,
                ];
            })->values();

            return [
                'deadline' => $deadline->toIso8601String(),
                'filieres' => $filieres,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'open',
                'periods' => $periods,
            ]
        ]);
    }
}

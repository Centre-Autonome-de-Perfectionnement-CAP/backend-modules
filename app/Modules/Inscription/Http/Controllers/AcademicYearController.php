<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\SubmissionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Academic Years",
 *     description="Gestion des années académiques et périodes"
 * )
 */
class AcademicYearController extends Controller
{
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
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year_start' => ['required','date'],
            'year_end' => ['required','date','after:year_start'],
            'submission_start' => ['required','date','after_or_equal:year_start'],
            'submission_end' => ['required','date','after:submission_start','before:year_end'],
            'departments' => ['sometimes','array','min:1'],
            'departments.*' => ['integer','exists:departments,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $startYear = date('Y', strtotime($data['year_start']));
            $endYear = date('Y', strtotime($data['year_end']));

            $exists = AcademicYear::where('academic_year', "$startYear-$endYear")->exists();
            if ($exists) {
                return response()->json(['message' => "L'année académique $startYear-$endYear existe déjà."], 422);
            }

            $year = new AcademicYear();
            $year->uuid = (string) Str::uuid();
            $year->academic_year = "$startYear-$endYear";
            $year->year_start = $data['year_start'];
            $year->year_end = $data['year_end'];
            $year->submission_start = $data['submission_start'];
            $year->submission_end = $data['submission_end'];
            $year->save();

            if (!empty($data['departments'])) {
                foreach ($data['departments'] as $departmentId) {
                    SubmissionPeriod::create([
                        'academic_year_id' => $year->id,
                        'department_id' => $departmentId,
                        'start_date' => $data['submission_start'],
                        'end_date' => $data['submission_end'],
                    ]);
                }
            }

            return response()->json(['success' => true, 'data' => $year->fresh()], 201);
        });
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
    public function update(Request $request, AcademicYear $academicYear): JsonResponse
    {
        $data = $request->validate([
            'year_start' => ['sometimes','date'],
            'year_end' => ['sometimes','date','after:year_start'],
            'submission_start' => ['sometimes','date'],
            'submission_end' => ['sometimes','date','after:submission_start'],
            'departments' => ['sometimes','array','min:1'],
            'departments.*' => ['integer','exists:departments,id'],
        ]);

        if (array_key_exists('year_start', $data)) { $academicYear->year_start = $data['year_start']; }
        if (array_key_exists('year_end', $data)) { $academicYear->year_end = $data['year_end']; }
        if (array_key_exists('submission_start', $data)) { $academicYear->submission_start = $data['submission_start']; }
        if (array_key_exists('submission_end', $data)) { $academicYear->submission_end = $data['submission_end']; }
        $academicYear->save();

        if (!empty($data['departments'])) {
            foreach ($data['departments'] as $departmentId) {
                $exists = SubmissionPeriod::where('academic_year_id', $academicYear->id)
                    ->where('department_id', $departmentId)
                    ->exists();
                if (!$exists) {
                    SubmissionPeriod::create([
                        'academic_year_id' => $academicYear->id,
                        'department_id' => $departmentId,
                        'start_date' => $academicYear->submission_start,
                        'end_date' => $academicYear->submission_end,
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'data' => $academicYear->fresh()]);
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
        $academicYear->delete();
        return response()->json(['success' => true]);
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
    public function addPeriods(Request $request, AcademicYear $academicYear): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required','date','after_or_equal:'.$academicYear->year_start],
            'end_date' => ['required','date','after:start_date','before_or_equal:'.$academicYear->year_end],
            'departments' => ['required','array','min:1'],
            'departments.*' => ['integer','exists:departments,id'],
        ]);

        foreach ($data['departments'] as $departmentId) {
            $overlap = SubmissionPeriod::where('department_id', $departmentId)
                ->where('academic_year_id', $academicYear->id)
                ->where(function ($q) use ($data) {
                    $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                      ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                      ->orWhere(function ($qq) use ($data) {
                          $qq->where('start_date', '<=', $data['start_date'])
                             ->where('end_date', '>=', $data['end_date']);
                      });
                })
                ->exists();
            if ($overlap) {
                return response()->json(['message' => "Le département $departmentId a déjà une période qui chevauche."], 422);
            }

            SubmissionPeriod::create([
                'academic_year_id' => $academicYear->id,
                'department_id' => $departmentId,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);
        }

        return response()->json(['success' => true], 201);
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
    public function extendPeriods(Request $request, AcademicYear $academicYear): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required','date'],
            'old_end_date' => ['required','date','after:start_date'],
            'new_end_date' => ['required','date','after:old_end_date','before_or_equal:'.$academicYear->year_end],
            'departments' => ['required','array','min:1'],
            'departments.*' => ['integer','exists:departments,id'],
        ]);

        SubmissionPeriod::where('academic_year_id', $academicYear->id)
            ->where('start_date', $data['start_date'])
            ->where('end_date', $data['old_end_date'])
            ->whereIn('department_id', $data['departments'])
            ->update(['end_date' => $data['new_end_date']]);

        return response()->json(['success' => true]);
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
    public function deletePeriods(Request $request, AcademicYear $academicYear): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after:start_date'],
            'departments' => ['required','array','min:1'],
            'departments.*' => ['integer','exists:departments,id'],
        ]);

        SubmissionPeriod::where('academic_year_id', $academicYear->id)
            ->where('start_date', $data['start_date'])
            ->where('end_date', $data['end_date'])
            ->whereIn('department_id', $data['departments'])
            ->delete();

        return response()->json(['success' => true]);
    }
}

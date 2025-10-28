<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\AcademicYearService;
use App\Modules\Inscription\Services\EntryDiplomaService;
use App\Modules\Inscription\Http\Resources\AcademicYearResource;
use Illuminate\Http\JsonResponse;

/**
 * Controller public pour les données de référence nécessaires aux candidatures
 */
class PublicReferenceController extends Controller
{
    public function __construct(
        protected AcademicYearService $academicYearService,
        protected EntryDiplomaService $diplomaService
    ) {}

    /**
     * Liste publique des années académiques
     */
    public function academicYears(): JsonResponse
    {
        $years = $this->academicYearService->getAllYears();

        // Simplifier les données pour éviter les problèmes de sérialisation
        $data = $years->map(function($year) {
            return [
                'id' => $year->id,
                'academic_year' => $year->academic_year,
                'year_start' => $year->year_start,
                'year_end' => $year->year_end,
                'submission_start' => $year->submission_start,
                'submission_end' => $year->submission_end,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Liste des années académiques pour un département
     * Retourne uniquement les années ayant des périodes de soumission actives pour ce département
     */
    public function academicYearsForDepartment(int $departmentId): JsonResponse
    {
        $now = now();
        
        // Récupérer les années académiques qui ont des périodes de soumission actives pour ce département
        $years = \App\Modules\Inscription\Models\AcademicYear::whereHas('submissionPeriods', function ($query) use ($departmentId, $now) {
            $query->where('department_id', $departmentId)
                  ->where('start_date', '<=', $now)
                  ->where('end_date', '>=', $now);
        })->get();

        $data = $years->map(function($year) {
            return [
                'id' => $year->id,
                'academic_year' => $year->academic_year,
                'year_start' => $year->year_start,
                'year_end' => $year->year_end,
                'submission_start' => $year->submission_start,
                'submission_end' => $year->submission_end,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Liste publique des diplômes d'entrée
     */
    public function entryDiplomas(): JsonResponse
    {
        $diplomas = $this->diplomaService->getAllDiplomas();

        return response()->json([
            'success' => true,
            'data' => $diplomas
        ]);
    }
}

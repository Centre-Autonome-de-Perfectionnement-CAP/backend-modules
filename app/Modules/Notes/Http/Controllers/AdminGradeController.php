<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Services\LmdGradeService;
use App\Modules\Notes\Services\OldGradeService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGradeController extends Controller
{
    use ApiResponse, HasPagination;

    private LmdGradeService $lmdGradeService;
    private OldGradeService $oldGradeService;

    public function __construct(LmdGradeService $lmdGradeService, OldGradeService $oldGradeService)
    {
        $this->lmdGradeService = $lmdGradeService;
        $this->oldGradeService = $oldGradeService;
    }

    /**
     * Dashboard des notes pour l'administration
     */
    public function dashboard(Request $request): JsonResponse
    {
        $academicYearId = $request->input('academic_year_id');
        
        $stats = [
            'total_evaluations' => $this->lmdGradeService->getTotalEvaluations($academicYearId),
            'completed_evaluations' => $this->lmdGradeService->getCompletedEvaluations($academicYearId),
            'pending_evaluations' => $this->lmdGradeService->getPendingEvaluations($academicYearId),
            'average_success_rate' => $this->lmdGradeService->getAverageSuccessRate($academicYearId),
            'programs_by_department' => $this->lmdGradeService->getProgramsByDepartment($academicYearId),
            'recent_activities' => $this->lmdGradeService->getRecentActivities($academicYearId),
        ];

        return $this->successResponse($stats, 'Dashboard récupéré avec succès');
    }

    /**
     * Obtient les notes par filière et niveau
     */
    public function getGradesByDepartmentLevel(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|string',
            'program_id' => 'nullable|exists:programs,id',
            'cohort' => 'nullable|string'
        ]);

        $result = $this->lmdGradeService->getGradesByFilters(
            $request->input('academic_year_id'),
            $request->input('department_id'),
            $request->input('level'),
            $request->input('program_id'),
            $request->input('cohort')
        );

        return $this->successResponse($result, 'Notes récupérées avec succès');
    }

    /**
     * Obtient les détails d'un programme spécifique
     */
    public function getProgramDetails(Request $request, int $programId): JsonResponse
    {
        $request->validate([
            'cohort' => 'nullable|string'
        ]);

        $result = $this->lmdGradeService->getProgramDetailsForAdmin(
            $programId,
            $request->input('cohort')
        );

        return $this->successResponse($result, 'Détails du programme récupérés avec succès');
    }

    /**
     * Exporte les notes par filière
     */
    public function exportGradesByDepartment(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'department_id' => 'required|exists:departments,id',
            'level' => 'nullable|string',
            'format' => 'in:pdf,excel',
            'cohort' => 'nullable|string'
        ]);

        $result = $this->lmdGradeService->exportGradesByDepartment(
            $request->input('academic_year_id'),
            $request->input('department_id'),
            $request->input('level'),
            $request->input('format', 'pdf'),
            $request->input('cohort')
        );

        return $this->successResponse($result, 'Export généré avec succès');
    }


}
<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notes\Services\CourseRetakeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseRetakeController extends Controller
{
    use ApiResponse;

    private CourseRetakeService $retakeService;

    public function __construct(CourseRetakeService $retakeService)
    {
        $this->retakeService = $retakeService;
    }

    public function getStudentRetakes(Request $request): JsonResponse
    {
        $studentPendingStudentId = $request->user()->studentPendingStudents()->first()?->id;

        if (!$studentPendingStudentId) {
            return $this->notFoundResponse('Étudiant non trouvé');
        }

        $retakes = $this->retakeService->getStudentRetakes(
            $studentPendingStudentId,
            $request->input('academic_year_id')
        );

        return $this->successResponse($retakes, 'Reprises récupérées avec succès');
    }

    public function updateRetakestatus(Request $request, int $retakeId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,passed,failed',
            'final_grade' => 'nullable|numeric|min:0|max:20'
        ]);

        $result = $this->retakeService->updateRetakestatus(
            $retakeId,
            $request->status,
            $request->final_grade
        );

        return $this->updatedResponse($result, 'status de reprise mis à jour');
    }
}

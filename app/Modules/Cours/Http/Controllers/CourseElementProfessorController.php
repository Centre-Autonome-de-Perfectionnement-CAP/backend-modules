<?php

namespace App\Modules\Cours\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cours\Models\CourseElement;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\Cours\Http\Requests\CreateCourseElementProfessorRequest;
use App\Modules\Cours\Http\Requests\UpdateCourseElementProfessorRequest;
use App\Modules\Cours\Services\CourseElementProfessorService;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseElementProfessorController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected CourseElementProfessorService $service
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['course_element_id', 'professor_id', 'search']);
        $perPage = $this->getPerPage($request);
        
        $assignments = $this->service->getAll($filters, $perPage);

        return $this->successPaginatedResponse(
            $assignments,
            'Associations récupérées avec succès'
        );
    }

    public function store(CreateCourseElementProfessorRequest $request): JsonResponse
    {
        $assignment = $this->service->create($request->validated());

        return $this->createdResponse(
            $assignment,
            'Association créée avec succès'
        );
    }

    public function show(CourseElementProfessor $courseElementProfessor): JsonResponse
    {
        return $this->successResponse(
            $courseElementProfessor->load(['courseElement.teachingUnit', 'professor']),
            'Association récupérée avec succès'
        );
    }

    public function update(UpdateCourseElementProfessorRequest $request, CourseElementProfessor $courseElementProfessor): JsonResponse
    {
        $assignment = $this->service->update($courseElementProfessor, $request->validated());

        return $this->updatedResponse(
            $assignment,
            'Association mise à jour avec succès'
        );
    }

    public function destroy(CourseElementProfessor $courseElementProfessor): JsonResponse
    {
        $this->service->delete($courseElementProfessor);

        return $this->deletedResponse('Association supprimée avec succès');
    }

    public function getByCourseElement(CourseElement $courseElement): JsonResponse
    {
        $assignments = $this->service->getByCourseElement($courseElement->id);

        return $this->successResponse(
            $assignments,
            'Professeurs assignés récupérés avec succès'
        );
    }

    public function renewForNextYear(Request $request): JsonResponse
    {
        $request->validate([
            'current_academic_year_id' => 'required|exists:academic_years,id',
            'next_academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $result = $this->service->renewForNextYear(
            $request->current_academic_year_id,
            $request->next_academic_year_id
        );

        return $this->successResponse($result, "{$result['created']} association(s) reconduite(s)");
    }
}

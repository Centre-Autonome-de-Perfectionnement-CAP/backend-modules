<?php

namespace App\Modules\Cours\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cours\Models\CourseElement;
use App\Modules\Cours\Http\Requests\CreateCourseElementRequest;
use App\Modules\Cours\Http\Requests\UpdateCourseElementRequest;
use App\Modules\Cours\Http\Requests\AttachProfessorRequest;
use App\Modules\Cours\Http\Resources\CourseElementResource;
use App\Modules\Cours\Services\CourseElementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

class CourseElementController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected CourseElementService $courseElementService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'teaching_unit_id', 'credits', 'sort_by', 'sort_order']);
        $perPage = $this->getPerPage($request);
        
        $courseElements = $this->courseElementService->getAll($filters, $perPage);

        $courseElements->setCollection(
            CourseElementResource::collection($courseElements->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $courseElements,
            'Éléments de cours récupérés avec succès'
        );
    }

    public function store(CreateCourseElementRequest $request): JsonResponse
    {
        $courseElement = $this->courseElementService->create($request->validated());

        return $this->createdResponse(
            new CourseElementResource($courseElement->load('teachingUnit')),
            'Élément de cours créé avec succès'
        );
    }

    public function show(CourseElement $courseElement): JsonResponse
    {
        return $this->successResponse(
            new CourseElementResource($courseElement->load(['teachingUnit', 'resources.file'])),
            'Élément de cours récupéré avec succès'
        );
    }

    public function update(UpdateCourseElementRequest $request, CourseElement $courseElement): JsonResponse
    {
        $courseElement = $this->courseElementService->update($courseElement, $request->validated());

        return $this->updatedResponse(
            new CourseElementResource($courseElement),
            'Élément de cours mis à jour avec succès'
        );
    }

    public function destroy(CourseElement $courseElement): JsonResponse
    {
        $this->courseElementService->delete($courseElement);

        return $this->deletedResponse('Élément de cours supprimé avec succès');
    }

    /**
     * Attacher un professeur à un élément de cours
     */
    public function attachProfessor(AttachProfessorRequest $request, CourseElement $courseElement): JsonResponse
    {
        $this->courseElementService->attachProfessor($courseElement, $request->professor_id);

        return $this->successResponse(
            null,
            'Professeur attaché avec succès'
        );
    }

    /**
     * Détacher un professeur d'un élément de cours
     */
    public function detachProfessor(AttachProfessorRequest $request, CourseElement $courseElement): JsonResponse
    {
        $this->courseElementService->detachProfessor($courseElement, $request->professor_id);

        return $this->successResponse(
            null,
            'Professeur détaché avec succès'
        );
    }

    /**
     * Liste des professeurs assignés à un élément de cours
     */
    public function getProfessors(CourseElement $courseElement): JsonResponse
    {
        $professors = $courseElement->professors()->get();

        return $this->successResponse(
            $professors->map(function ($professor) {
                return [
                    'id' => $professor->id,
                    'first_name' => $professor->first_name,
                    'last_name' => $professor->last_name,
                    'full_name' => $professor->full_name,
                    'email' => $professor->email,
                    'phone' => $professor->phone,
                ];
            }),
            'Professeurs récupérés avec succès'
        );
    }

    /**
     * Liste des ressources d'un élément de cours
     */
    public function getResources(CourseElement $courseElement): JsonResponse
    {
        $resources = $courseElement->resources()->with('file')->get();

        return $this->successResponse(
            $resources,
            'Ressources récupérées avec succès'
        );
    }
}

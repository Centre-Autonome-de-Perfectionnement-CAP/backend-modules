<?php

namespace App\Modules\Cours\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cours\Models\CourseElementResource as CourseResource;
use App\Modules\Cours\Http\Requests\CreateCourseElementResourceRequest;
use App\Modules\Cours\Http\Requests\UpdateCourseElementResourceRequest;
use App\Modules\Cours\Http\Resources\CourseElementResourceResource;
use App\Modules\Cours\Services\CourseElementResourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

class CourseElementResourceController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected CourseElementResourceService $resourceService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['course_element_id', 'resource_type', 'sort_by', 'sort_order']);
        if ($request->filled('is_public')) {
            $filters['is_public'] = $request->boolean('is_public');
        }
        $perPage = $this->getPerPage($request);
        
        $resources = $this->resourceService->getAll($filters, $perPage);

        $resources->getCollection()->load(['courseElement', 'file']);
        $resources->setCollection(
            CourseElementResourceResource::collection($resources->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $resources,
            'Ressources récupérées avec succès'
        );
    }

    public function store(CreateCourseElementResourceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $resource = $this->resourceService->create($data, $request->file('file'), auth()->id());

        return $this->createdResponse(
            new CourseElementResourceResource($resource->load('file')),
            'Ressource créée avec succès'
        );
    }

    public function show(CourseResource $courseElementResource): JsonResponse
    {
        return $this->successResponse(
            new CourseElementResourceResource($courseElementResource->load(['courseElement', 'file'])),
            'Ressource récupérée avec succès'
        );
    }

    public function update(UpdateCourseElementResourceRequest $request, CourseResource $courseElementResource): JsonResponse
    {
        $resource = $this->resourceService->update($courseElementResource, $request->validated());

        return $this->updatedResponse(
            new CourseElementResourceResource($resource),
            'Ressource mise à jour avec succès'
        );
    }

    public function destroy(CourseResource $courseElementResource): JsonResponse
    {
        $this->resourceService->delete($courseElementResource, auth()->id());

        return $this->deletedResponse('Ressource supprimée avec succès');
    }
}

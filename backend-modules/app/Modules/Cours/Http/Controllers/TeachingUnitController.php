<?php

namespace App\Modules\Cours\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cours\Models\TeachingUnit;
use App\Modules\Cours\Http\Requests\CreateTeachingUnitRequest;
use App\Modules\Cours\Http\Requests\UpdateTeachingUnitRequest;
use App\Modules\Cours\Http\Resources\TeachingUnitResource;
use App\Modules\Cours\Services\TeachingUnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

class TeachingUnitController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected TeachingUnitService $teachingUnitService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'sort_by', 'sort_order']);
        $perPage = $this->getPerPage($request);
        
        $teachingUnits = $this->teachingUnitService->getAll($filters, $perPage);
        $teachingUnits->setCollection(
            TeachingUnitResource::collection($teachingUnits->getCollection())->collection
        );

        return $this->successPaginatedResponse(
            $teachingUnits,
            'Unités d\'enseignement récupérées avec succès'
        );
    }

    public function store(CreateTeachingUnitRequest $request): JsonResponse
    {
        $teachingUnit = $this->teachingUnitService->create($request->validated());

        return $this->createdResponse(
            new TeachingUnitResource($teachingUnit),
            'Unité d\'enseignement créée avec succès'
        );
    }

    public function show(TeachingUnit $teachingUnit): JsonResponse
    {
        return $this->successResponse(
            new TeachingUnitResource($teachingUnit->load('courseElements')),
            'Unité d\'enseignement récupérée avec succès'
        );
    }

    public function update(UpdateTeachingUnitRequest $request, TeachingUnit $teachingUnit): JsonResponse
    {
        $teachingUnit = $this->teachingUnitService->update($teachingUnit, $request->validated());

        return $this->updatedResponse(
            new TeachingUnitResource($teachingUnit),
            'Unité d\'enseignement mise à jour avec succès'
        );
    }

    public function destroy(TeachingUnit $teachingUnit): JsonResponse
    {
        $this->teachingUnitService->delete($teachingUnit);

        return $this->deletedResponse('Unité d\'enseignement supprimée avec succès');
    }

    /**
     * Liste des éléments de cours (ECUE) d'une unité d'enseignement
     */
    public function getCourseElements(TeachingUnit $teachingUnit): JsonResponse
    {
        $courseElements = $teachingUnit->courseElements()->get();

        return $this->successResponse(
            $courseElements->map(function ($courseElement) {
                return [
                    'id' => $courseElement->id,
                    'name' => $courseElement->name,
                    'code' => $courseElement->code,
                    'credits' => $courseElement->credits,
                    'created_at' => $courseElement->created_at?->toISOString(),
                ];
            }),
            'Éléments de cours récupérés avec succès'
        );
    }
}

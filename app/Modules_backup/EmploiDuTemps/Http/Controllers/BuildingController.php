<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmploiDuTemps\Models\Building;
use App\Modules\EmploiDuTemps\Http\Requests\CreateBuildingRequest;
use App\Modules\EmploiDuTemps\Http\Requests\UpdateBuildingRequest;
use App\Modules\EmploiDuTemps\Http\Resources\BuildingResource;
use App\Modules\EmploiDuTemps\Services\BuildingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

class BuildingController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected BuildingService $buildingService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order']);
        $perPage = $this->getPerPage($request);
        
        $buildings = $this->buildingService->getAll($filters, $perPage);

        // Transformer les données avec la Resource
        $transformedData = BuildingResource::collection($buildings->items());
        
        // Créer un nouveau paginator avec les données transformées
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $transformedData,
            $buildings->total(),
            $buildings->perPage(),
            $buildings->currentPage(),
            ['path' => $request->url()]
        );

        return $this->successPaginatedResponse(
            $paginator,
            'Bâtiments récupérés avec succès'
        );
    }

    public function store(CreateBuildingRequest $request): JsonResponse
    {
        $building = $this->buildingService->create($request->validated());

        return $this->createdResponse(
            new BuildingResource($building),
            'Bâtiment créé avec succès'
        );
    }

    public function show(Building $building): JsonResponse
    {
        return $this->successResponse(
            new BuildingResource($building->load('rooms')),
            'Bâtiment récupéré avec succès'
        );
    }

    public function update(UpdateBuildingRequest $request, Building $building): JsonResponse
    {
        $building = $this->buildingService->update($building, $request->validated());

        return $this->updatedResponse(
            new BuildingResource($building),
            'Bâtiment mis à jour avec succès'
        );
    }

    public function destroy(Building $building): JsonResponse
    {
        $this->buildingService->delete($building);

        return $this->deletedResponse('Bâtiment supprimé avec succès');
    }
}

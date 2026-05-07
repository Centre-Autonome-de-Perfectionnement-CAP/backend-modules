<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmploiDuTemps\Models\TimeSlot;
use App\Modules\EmploiDuTemps\Http\Requests\CreateTimeSlotRequest;
use App\Modules\EmploiDuTemps\Http\Requests\UpdateTimeSlotRequest;
use App\Modules\EmploiDuTemps\Http\Resources\TimeSlotResource;
use App\Modules\EmploiDuTemps\Services\TimeSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

class TimeSlotController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected TimeSlotService $timeSlotService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'day_of_week', 'type', 'sort_by', 'sort_order']);
        $perPage = $this->getPerPage($request);
        
        $timeSlots = $this->timeSlotService->getAll($filters, $perPage);

        $transformedData = TimeSlotResource::collection($timeSlots->items());
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $transformedData,
            $timeSlots->total(),
            $timeSlots->perPage(),
            $timeSlots->currentPage(),
            ['path' => $request->url()]
        );

        return $this->successPaginatedResponse(
            $paginator,
            'Créneaux horaires récupérés avec succès'
        );
    }

    public function store(CreateTimeSlotRequest $request): JsonResponse
    {
        $timeSlot = $this->timeSlotService->create($request->validated());

        return $this->createdResponse(
            new TimeSlotResource($timeSlot),
            'Créneau horaire créé avec succès'
        );
    }

    public function show(TimeSlot $timeSlot): JsonResponse
    {
        return $this->successResponse(
            new TimeSlotResource($timeSlot),
            'Créneau horaire récupéré avec succès'
        );
    }

    public function update(UpdateTimeSlotRequest $request, TimeSlot $timeSlot): JsonResponse
    {
        $timeSlot = $this->timeSlotService->update($timeSlot, $request->validated());

        return $this->updatedResponse(
            new TimeSlotResource($timeSlot),
            'Créneau horaire mis à jour avec succès'
        );
    }

    public function destroy(TimeSlot $timeSlot): JsonResponse
    {
        $this->timeSlotService->delete($timeSlot);

        return $this->deletedResponse('Créneau horaire supprimé avec succès');
    }

    /**
     * Récupérer les créneaux d'un jour spécifique
     */
    public function getByDay(Request $request, string $day): JsonResponse
    {
        $timeSlots = $this->timeSlotService->getByDay($day);

        return $this->successResponse(
            TimeSlotResource::collection($timeSlots),
            'Créneaux horaires récupérés avec succès'
        );
    }
}

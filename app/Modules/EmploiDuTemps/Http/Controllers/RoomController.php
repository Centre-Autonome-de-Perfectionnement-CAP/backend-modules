<?php

namespace App\Modules\EmploiDuTemps\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmploiDuTemps\Models\Room;
use App\Modules\EmploiDuTemps\Http\Requests\CreateRoomRequest;
use App\Modules\EmploiDuTemps\Http\Requests\UpdateRoomRequest;
use App\Modules\EmploiDuTemps\Http\Resources\RoomResource;
use App\Modules\EmploiDuTemps\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasPagination;

class RoomController extends Controller
{
    use ApiResponse, HasPagination;

    public function __construct(
        protected RoomService $roomService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'building_id',
            'room_type',
            'is_available',
            'min_capacity',
            'sort_by',
            'sort_order'
        ]);
        $perPage = $this->getPerPage($request);
        
        $rooms = $this->roomService->getAll($filters, $perPage);

        $transformedData = RoomResource::collection($rooms->items());
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $transformedData,
            $rooms->total(),
            $rooms->perPage(),
            $rooms->currentPage(),
            ['path' => $request->url()]
        );

        return $this->successPaginatedResponse(
            $paginator,
            'Salles récupérées avec succès'
        );
    }

    public function store(CreateRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create($request->validated());

        return $this->createdResponse(
            new RoomResource($room->load('building')),
            'Salle créée avec succès'
        );
    }

    public function show(Room $room): JsonResponse
    {
        return $this->successResponse(
            new RoomResource($room->load(['building', 'scheduledCourses'])),
            'Salle récupérée avec succès'
        );
    }

    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room = $this->roomService->update($room, $request->validated());

        return $this->updatedResponse(
            new RoomResource($room),
            'Salle mise à jour avec succès'
        );
    }

    public function destroy(Room $room): JsonResponse
    {
        $this->roomService->delete($room);

        return $this->deletedResponse('Salle supprimée avec succès');
    }

    /**
     * Récupérer les salles disponibles pour un créneau et une date donnés
     */
    public function getAvailable(Request $request): JsonResponse
    {
        $request->validate([
            'time_slot_id' => 'required|exists:time_slots,id',
            'date' => 'required|date',
            'min_capacity' => 'nullable|integer|min:1',
        ]);

        $rooms = $this->roomService->getAvailableRooms(
            $request->time_slot_id,
            $request->date,
            $request->min_capacity
        );

        return $this->successResponse(
            RoomResource::collection($rooms),
            'Salles disponibles récupérées avec succès'
        );
    }
}

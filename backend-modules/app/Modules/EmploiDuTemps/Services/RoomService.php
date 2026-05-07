<?php

namespace App\Modules\EmploiDuTemps\Services;

use App\Modules\EmploiDuTemps\Models\Room;
use Illuminate\Support\Facades\Log;
use Exception;

class RoomService
{
    /**
     * Récupérer toutes les salles avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Room::query()->with('building');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['building_id'])) {
            $query->where('building_id', $filters['building_id']);
        }

        if (!empty($filters['room_type'])) {
            $query->where('room_type', $filters['room_type']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        if (!empty($filters['min_capacity'])) {
            $query->where('capacity', '>=', $filters['min_capacity']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer une nouvelle salle
     */
    public function create(array $data): Room
    {
        $room = Room::create($data);

        Log::info('Salle créée', [
            'room_id' => $room->id,
            'code' => $room->code,
        ]);

        return $room;
    }

    /**
     * Récupérer une salle par ID
     */
    public function getById(int $id): ?Room
    {
        return Room::with(['building', 'scheduledCourses'])->find($id);
    }

    /**
     * Mettre à jour une salle
     */
    public function update(Room $room, array $data): Room
    {
        $room->update($data);

        Log::info('Salle mise à jour', [
            'room_id' => $room->id,
        ]);

        return $room->fresh(['building']);
    }

    /**
     * Supprimer une salle
     */
    public function delete(Room $room): bool
    {
        try {
            $room->delete();

            Log::info('Salle supprimée', [
                'room_id' => $room->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de la salle', [
                'room_id' => $room->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Récupérer les salles disponibles pour un créneau donné
     */
    public function getAvailableRooms(int $timeSlotId, string $date, ?int $minCapacity = null)
    {
        $query = Room::query()
            ->where('is_available', true)
            ->whereDoesntHave('scheduledCourses', function ($q) use ($timeSlotId, $date) {
                $q->where('time_slot_id', $timeSlotId)
                  ->where('start_date', '<=', $date)
                  ->where(function ($query) use ($date) {
                      $query->whereNull('recurrence_end_date')
                            ->orWhere('recurrence_end_date', '>=', $date);
                  })
                  ->where('is_cancelled', false);
            });

        if ($minCapacity) {
            $query->where('capacity', '>=', $minCapacity);
        }

        return $query->with('building')->get();
    }
}

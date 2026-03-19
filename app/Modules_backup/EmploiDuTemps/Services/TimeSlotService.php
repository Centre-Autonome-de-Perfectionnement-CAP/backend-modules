<?php

namespace App\Modules\EmploiDuTemps\Services;

use App\Modules\EmploiDuTemps\Models\TimeSlot;
use Illuminate\Support\Facades\Log;
use Exception;

class TimeSlotService
{
    /**
     * Récupérer tous les créneaux horaires avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = TimeSlot::query();

        if (!empty($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $filters['sort_by'] ?? 'start_time';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer un nouveau créneau horaire
     */
    public function create(array $data): TimeSlot
    {
        $timeSlot = TimeSlot::create($data);

        Log::info('Créneau horaire créé', [
            'time_slot_id' => $timeSlot->id,
            'day' => $timeSlot->day_of_week,
            'time' => $timeSlot->start_time . ' - ' . $timeSlot->end_time,
        ]);

        return $timeSlot;
    }

    /**
     * Récupérer un créneau horaire par ID
     */
    public function getById(int $id): ?TimeSlot
    {
        return TimeSlot::find($id);
    }

    /**
     * Mettre à jour un créneau horaire
     */
    public function update(TimeSlot $timeSlot, array $data): TimeSlot
    {
        $timeSlot->update($data);

        Log::info('Créneau horaire mis à jour', [
            'time_slot_id' => $timeSlot->id,
        ]);

        return $timeSlot->fresh();
    }

    /**
     * Supprimer un créneau horaire
     */
    public function delete(TimeSlot $timeSlot): bool
    {
        try {
            $timeSlot->delete();

            Log::info('Créneau horaire supprimé', [
                'time_slot_id' => $timeSlot->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression du créneau horaire', [
                'time_slot_id' => $timeSlot->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Récupérer les créneaux disponibles pour un jour donné
     */
    public function getByDay(string $dayOfWeek)
    {
        return TimeSlot::where('day_of_week', $dayOfWeek)
            ->orderBy('start_time', 'asc')
            ->get();
    }
}

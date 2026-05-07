<?php

namespace App\Modules\EmploiDuTemps\Services;

use App\Modules\EmploiDuTemps\Models\Building;
use Illuminate\Support\Facades\Log;
use Exception;

class BuildingService
{
    /**
     * Récupérer tous les bâtiments avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Building::query()->withCount('rooms');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer un nouveau bâtiment
     */
    public function create(array $data): Building
    {
        $building = Building::create($data);

        Log::info('Bâtiment créé', [
            'building_id' => $building->id,
            'code' => $building->code,
        ]);

        return $building;
    }

    /**
     * Récupérer un bâtiment par ID
     */
    public function getById(int $id): ?Building
    {
        return Building::with('rooms')->find($id);
    }

    /**
     * Mettre à jour un bâtiment
     */
    public function update(Building $building, array $data): Building
    {
        $building->update($data);

        Log::info('Bâtiment mis à jour', [
            'building_id' => $building->id,
        ]);

        return $building->fresh(['rooms']);
    }

    /**
     * Supprimer un bâtiment
     */
    public function delete(Building $building): bool
    {
        try {
            $building->delete();

            Log::info('Bâtiment supprimé', [
                'building_id' => $building->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression du bâtiment', [
                'building_id' => $building->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

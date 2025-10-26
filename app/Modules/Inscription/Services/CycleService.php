<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\Cycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CycleService
{
    /**
     * Récupérer tous les cycles
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Cycle::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    /**
     * Créer un cycle
     */
    public function create(array $data): Cycle
    {
        return DB::transaction(function () use ($data) {
            $cycle = Cycle::create($data);

            Log::info('Cycle créé', [
                'cycle_id' => $cycle->id,
                'name' => $cycle->name,
            ]);

            return $cycle;
        });
    }

    /**
     * Récupérer par ID
     */
    public function getById(int $id): ?Cycle
    {
        return Cycle::find($id);
    }

    /**
     * Mettre à jour un cycle
     */
    public function update(Cycle $cycle, array $data): Cycle
    {
        return DB::transaction(function () use ($cycle, $data) {
            $cycle->update($data);

            Log::info('Cycle mis à jour', [
                'cycle_id' => $cycle->id,
            ]);

            return $cycle->fresh();
        });
    }

    /**
     * Supprimer un cycle
     */
    public function delete(Cycle $cycle): bool
    {
        try {
            $cycle->delete();

            Log::info('Cycle supprimé', [
                'cycle_id' => $cycle->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur suppression cycle', [
                'cycle_id' => $cycle->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Activer/Désactiver un cycle
     */
    public function toggleActive(Cycle $cycle): Cycle
    {
        $cycle->update(['is_active' => !$cycle->is_active]);

        Log::info('Statut du cycle changé', [
            'cycle_id' => $cycle->id,
            'is_active' => $cycle->is_active,
        ]);

        return $cycle->fresh();
    }
}

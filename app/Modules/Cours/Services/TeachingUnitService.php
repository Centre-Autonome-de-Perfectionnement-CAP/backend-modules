<?php

namespace App\Modules\Cours\Services;

use App\Modules\Cours\Models\TeachingUnit;
use Illuminate\Support\Facades\Log;
use Exception;

class TeachingUnitService
{
    /**
     * Récupérer toutes les unités d'enseignement avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = TeachingUnit::query()->with(['courseElements']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer une nouvelle unité d'enseignement
     */
    public function create(array $data): TeachingUnit
    {
        $teachingUnit = TeachingUnit::create($data);

        Log::info('Unité d\'enseignement créée', [
            'teaching_unit_id' => $teachingUnit->id,
            'code' => $teachingUnit->code,
        ]);

        return $teachingUnit;
    }

    /**
     * Récupérer une unité d'enseignement par ID
     */
    public function getById(int $id): ?TeachingUnit
    {
        return TeachingUnit::with(['courseElements'])->find($id);
    }

    /**
     * Mettre à jour une unité d'enseignement
     */
    public function update(TeachingUnit $teachingUnit, array $data): TeachingUnit
    {
        $teachingUnit->update($data);

        Log::info('Unité d\'enseignement mise à jour', [
            'teaching_unit_id' => $teachingUnit->id,
        ]);

        return $teachingUnit->fresh();
    }

    /**
     * Supprimer une unité d'enseignement
     */
    public function delete(TeachingUnit $teachingUnit): bool
    {
        try {
            $teachingUnit->delete();

            Log::info('Unité d\'enseignement supprimée', [
                'teaching_unit_id' => $teachingUnit->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de l\'unité d\'enseignement', [
                'teaching_unit_id' => $teachingUnit->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

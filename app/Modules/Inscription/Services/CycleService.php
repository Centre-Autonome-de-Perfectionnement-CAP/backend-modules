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

    /**
     * Récupère tous les cycles avec leurs départements
     */
    public function getAllWithDepartments()
    {
        return Cycle::with('departments')->get();
    }

    /**
     * Récupère tous les départements avec leurs périodes de soumission formatés pour le frontend
     */
    public function getAllDepartmentsWithPeriods()
    {
        $departments = \App\Modules\Inscription\Models\Department::with(['cycle', 'submissionPeriod'])->get();
        $today = \Illuminate\Support\Carbon::today();

        return $departments->map(function ($dept) use ($today) {
            $cycleName = strtolower($dept->cycle->name ?? '');
            if ($cycleName === 'ingénieur') {
                $cycleName = 'ingenierie';
            }

            // Trouver la période active ou la plus proche
            $activePeriod = null;
            $badge = null;
            $dateLimite = null;

            foreach ($dept->submissionPeriod as $p) {
                $start = \Illuminate\Support\Carbon::parse($p->start_date);
                $end = \Illuminate\Support\Carbon::parse($p->end_date);

                // Période en cours
                if ($today->between($start, $end)) {
                    $activePeriod = $p;
                    $badge = 'inscriptions-ouvertes';
                    $dateLimite = $end->format('Y-m-d');
                    break;
                }
                // Période future
                if ($start->gt($today)) {
                    if (!$activePeriod) {
                        $activePeriod = $p;
                        $badge = 'prochainement';
                        $dateLimite = $end->format('Y-m-d');
                    }
                }
            }

            // Si aucune période active/future, c'est fermé
            if (!$badge) {
                $badge = 'inscriptions-fermees';
            }

            return [
                'id' => $dept->id,
                'title' => $dept->name,
                'abbreviation' => $dept->abbreviation ?? '',
                'cycle' => $cycleName,
                'dateLimite' => $dateLimite,
                'image' => '',
                'badge' => $badge,
            ];
        })->values();
    }

    /**
     * Récupère les périodes d'inscription actives groupées par deadline
     */
    public function getNextDeadline()
    {
        $today = \Illuminate\Support\Carbon::now();

        // Récupérer toutes les périodes actives (end_date >= aujourd'hui)
        $activePeriods = \App\Modules\Inscription\Models\SubmissionPeriod::with(['department.cycle'])
            ->where('end_date', '>=', $today)
            ->orderBy('end_date', 'asc')
            ->get();

        if ($activePeriods->isEmpty()) {
            return [
                'status' => 'closed',
                'periods' => []
            ];
        }

        // Grouper par date de fin
        $groupedByDeadline = $activePeriods->groupBy(function ($period) {
            return \Illuminate\Support\Carbon::parse($period->end_date)->format('Y-m-d');
        });

        // Formater les données
        $periods = $groupedByDeadline->map(function ($periodsGroup, $dateKey) {
            $deadline = \Illuminate\Support\Carbon::parse($dateKey)->endOfDay();
            
            $filieres = $periodsGroup->filter(function ($period) {
                return $period->department !== null;
            })->map(function ($period) {
                $cycleName = strtolower($period->department->cycle->name ?? '');
                if ($cycleName === 'ingénieur') {
                    $cycleName = 'ingenierie';
                }

                return [
                    'id' => $period->department->id,
                    'name' => $period->department->name,
                    'abbreviation' => $period->department->abbreviation ?? '',
                    'cycle' => $cycleName,
                ];
            })->values();

            return [
                'deadline' => $deadline->toIso8601String(),
                'filieres' => $filieres,
            ];
        })->values();

        return [
            'status' => 'open',
            'periods' => $periods,
        ];
    }
}

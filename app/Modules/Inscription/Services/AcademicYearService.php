<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Models\ReclamationPeriod;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AcademicYearService
{
    /**
     * Récupérer toutes les années académiques
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = AcademicYear::query();

        if (!empty($filters['search'])) {
            $query->where('academic_year', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['is_current'])) {
            $query->where('is_current', true);
        }

        return $query->orderBy('year_start', 'desc')->paginate($perPage);
    }

    /**
     * Créer une année académique
     */
    public function create(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data) {
            $startYear = date('Y', strtotime($data['year_start']));
            $endYear = date('Y', strtotime($data['year_end']));
            $academicYearLabel = "$startYear-$endYear";

            // Vérifier si elle existe déjà
            $exists = AcademicYear::where('academic_year', $academicYearLabel)->exists();
            if ($exists) {
                throw new BusinessException(
                    message: "L'année académique $academicYearLabel existe déjà",
                    errorCode: 'ACADEMIC_YEAR_EXISTS',
                    statusCode: 409
                );
            }

            $year = AcademicYear::create([
                'uuid' => (string) Str::uuid(),
                'academic_year' => $academicYearLabel,
                'year_start' => $data['year_start'],
                'year_end' => $data['year_end'],
                'submission_start' => $data['submission_start'] ?? null,
                'submission_end' => $data['submission_end'] ?? null,
                'is_current' => true,
            ]);

            // Créer les périodes de soumission pour les départements
            if (!empty($data['departments']) && !empty($data['submission_start']) && !empty($data['submission_end'])) {
                foreach ($data['departments'] as $departmentId) {
                    SubmissionPeriod::create([
                        'academic_year_id' => $year->id,
                        'department_id' => $departmentId,
                        'start_date' => $data['submission_start'],
                        'end_date' => $data['submission_end'],
                    ]);
                }
            }

            Log::info('Année académique créée', [
                'academic_year_id' => $year->id,
                'label' => $academicYearLabel,
            ]);

            return $year->fresh();
        });
    }

    /**
     * Récupérer par ID
     */
    public function getById(int $id): ?AcademicYear
    {
        return AcademicYear::find($id);
    }

    /**
     * Mettre à jour une année académique
     */
    public function update(AcademicYear $academicYear, array $data): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $data) {
            // Mise à jour des dates si fournies
            $updateData = [];
            
            if (isset($data['year_start'])) {
                $updateData['year_start'] = $data['year_start'];
            }
            
            if (isset($data['year_end'])) {
                $updateData['year_end'] = $data['year_end'];
            }
            
            if (isset($data['submission_start'])) {
                $updateData['submission_start'] = $data['submission_start'];
            }
            
            if (isset($data['submission_end'])) {
                $updateData['submission_end'] = $data['submission_end'];
            }

            if (!empty($updateData)) {
                $academicYear->update($updateData);
            }

            // Mettre à jour les périodes de soumission si nécessaire
            if (isset($data['departments'])) {
                // Supprimer les anciennes périodes
                $academicYear->submissionPeriod()->delete();
                
                // Créer les nouvelles
                foreach ($data['departments'] as $departmentId) {
                    SubmissionPeriod::create([
                        'academic_year_id' => $academicYear->id,
                        'department_id' => $departmentId,
                        'start_date' => $data['submission_start'] ?? $academicYear->submission_start,
                        'end_date' => $data['submission_end'] ?? $academicYear->submission_end,
                    ]);
                }
            }

            Log::info('Année académique mise à jour', [
                'academic_year_id' => $academicYear->id,
            ]);

            return $academicYear->fresh();
        });
    }

    /**
     * Supprimer une année académique
     */
    public function delete(AcademicYear $academicYear): bool
    {
        try {
            $academicYear->delete();

            Log::info('Année académique supprimée', [
                'academic_year_id' => $academicYear->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur suppression année académique', [
                'academic_year_id' => $academicYear->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Définir comme année courante
     */
    public function setCurrent(AcademicYear $academicYear): AcademicYear
    {
        return DB::transaction(function () use ($academicYear) {
            // Désactiver toutes les autres
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
            
            // Activer celle-ci
            $academicYear->update(['is_current' => true]);

            Log::info('Année académique définie comme courante', [
                'academic_year_id' => $academicYear->id,
            ]);

            return $academicYear->fresh();
        });
    }

    /**
     * Récupérer l'année académique courante
     */
    public function getCurrent(): ?AcademicYear
    {
        return AcademicYear::where('is_current', true)
            ->first();
    }

    /**
     * Vérifier si les inscriptions sont ouvertes
     */
    public function isSubmissionOpen(): bool
    {
        $current = $this->getCurrent();
        
        if (!$current) {
            return false;
        }

        $now = now();
        return $now->between($current->submission_start, $current->submission_end);
    }

    /**
     * Récupérer toutes les années académiques (sans pagination)
     */
    public function getAllYears()
    {
        return AcademicYear::orderBy('year_start', 'desc')->get();
    }

    /**
     * Récupérer les années académiques pour un département spécifique
     * Retourne uniquement les années ayant des périodes de soumission actives pour ce département
     */
    public function getYearsForDepartment(int $departmentId)
    {
        $now = now();
        
        return AcademicYear::whereHas('submissionPeriods', function ($query) use ($departmentId, $now) {
            $query->where('department_id', $departmentId)
                  ->where('start_date', '<=', $now)
                  ->where('end_date', '>=', $now);
        })->orderBy('year_start', 'desc')->get();
    }

    /**
     * Ajouter des périodes de soumission pour des départements
     */
    public function addPeriods(AcademicYear $academicYear, array $data): void
    {
        DB::transaction(function () use ($academicYear, $data) {
            $type = $data['type'] ?? 'depot';

            if ($type === 'reclamation') {
                // Pour les périodes de réclamation, créer une seule période sans départements spécifiques
                ReclamationPeriod::create([
                    'academic_year_id' => $academicYear->id,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'is_active' => true,
                ]);

                Log::info('Période de réclamation ajoutée', [
                    'academic_year_id' => $academicYear->id,
                ]);
            } else {
                // Pour les périodes de dépôt (depot/submission)
                foreach ($data['departments'] as $departmentId) {
                    // Vérifier les chevauchements
                    $overlap = SubmissionPeriod::where('department_id', $departmentId)
                        ->where('academic_year_id', $academicYear->id)
                        ->where(function ($q) use ($data) {
                            $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                              ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                              ->orWhere(function ($qq) use ($data) {
                                  $qq->where('start_date', '<=', $data['start_date'])
                                     ->where('end_date', '>=', $data['end_date']);
                              });
                        })
                        ->exists();

                    if ($overlap) {
                        throw new \App\Exceptions\PeriodOverlapException($departmentId);
                    }

                    SubmissionPeriod::create([
                        'academic_year_id' => $academicYear->id,
                        'department_id' => $departmentId,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                    ]);
                }

                Log::info('Périodes de dépôt ajoutées', [
                    'academic_year_id' => $academicYear->id,
                    'departments' => $data['departments'],
                ]);
            }
        });
    }

    /**
     * Étendre les périodes de soumission
     */
    public function extendPeriods(AcademicYear $academicYear, array $data): int
    {
        return DB::transaction(function () use ($academicYear, $data) {
            $updated = SubmissionPeriod::where('academic_year_id', $academicYear->id)
                ->where('start_date', $data['start_date'])
                ->where('end_date', $data['old_end_date'])
                ->whereIn('department_id', $data['departments'])
                ->update(['end_date' => $data['new_end_date']]);

            Log::info('Périodes étendues', [
                'academic_year_id' => $academicYear->id,
                'departments' => $data['departments'],
                'updated_count' => $updated,
            ]);

            return $updated;
        });
    }

    /**
     * Supprimer des périodes de soumission
     */
    public function deletePeriods(AcademicYear $academicYear, array $data): int
    {
        return DB::transaction(function () use ($academicYear, $data) {
            $deleted = SubmissionPeriod::where('academic_year_id', $academicYear->id)
                ->where('start_date', $data['start_date'])
                ->where('end_date', $data['end_date'])
                ->whereIn('department_id', $data['departments'])
                ->delete();

            Log::info('Périodes supprimées', [
                'academic_year_id' => $academicYear->id,
                'departments' => $data['departments'],
                'deleted_count' => $deleted,
            ]);

            return $deleted;
        });
    }

    /**
     * Récupérer toutes les périodes d'une année académique
     */
    public function getPeriods(AcademicYear $academicYear): array
    {
        $periods = [];

        // Récupérer les périodes de soumission (dépôt)
        $submissionPeriods = SubmissionPeriod::where('academic_year_id', $academicYear->id)
            ->with('department')
            ->get()
            ->groupBy(function ($period) {
                $startDate = $period->start_date ? $period->start_date->format('Y-m-d') : 'null';
                $endDate = $period->end_date ? $period->end_date->format('Y-m-d') : 'null';
                return $startDate . '_' . $endDate;
            });

        foreach ($submissionPeriods as $key => $groupedPeriods) {
            $firstPeriod = $groupedPeriods->first();
            $departments = $groupedPeriods->map(function ($period) {
                return $period->department->abbreviation ?? $period->department->name;
            })->toArray();

            $periods[] = [
                'type' => 'depot',
                'start' => $firstPeriod->start_date ? $firstPeriod->start_date->format('d/m/Y') : '',
                'end' => $firstPeriod->end_date ? $firstPeriod->end_date->format('d/m/Y') : '',
                'filieres' => $departments,
            ];
        }

        // Récupérer les périodes de réclamation
        $reclamationPeriods = $academicYear->reclamationPeriod;
        
        foreach ($reclamationPeriods as $period) {
            $periods[] = [
                'type' => 'reclamation',
                'start' => $period->start_date ? $period->start_date->format('d/m/Y') : '',
                'end' => $period->end_date ? $period->end_date->format('d/m/Y') : '',
                'filieres' => [], // Les réclamations n'ont pas de départements spécifiques
            ];
        }

        return $periods;
    }
}

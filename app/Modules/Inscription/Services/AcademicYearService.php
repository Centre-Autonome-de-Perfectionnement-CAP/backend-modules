<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\SubmissionPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

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
                throw new Exception("L'année académique $academicYearLabel existe déjà.");
            }

            $year = AcademicYear::create([
                'uuid' => (string) Str::uuid(),
                'academic_year' => $academicYearLabel,
                'year_start' => $data['year_start'],
                'year_end' => $data['year_end'],
                'submission_start' => $data['submission_start'],
                'submission_end' => $data['submission_end'],
                'is_current' => false,
            ]);

            // Créer les périodes de soumission pour les départements
            if (!empty($data['departments'])) {
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
}

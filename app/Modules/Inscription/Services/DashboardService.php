<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use App\Services\DatabaseAdapter;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get dashboard statistics
     */
    public function getStats(): array
    {
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();
        $anneeAcademique = $currentAcademicYear ? $currentAcademicYear->academic_year : null;

        $dossiersAttente = PendingStudent::where('status', 'pending')->count();
        $inscritsCap = PendingStudent::where('status', 'approved')->count();
        $nombreFilieres = Department::count();
        $nombreCycles = Cycle::count();

        return [
            'inscritsCap' => $inscritsCap,
            'dossiersAttente' => $dossiersAttente,
            'anneeAcademique' => $anneeAcademique,
            'nombreFilieres' => $nombreFilieres,
            'nombreCycles' => $nombreCycles,
        ];
    }

    /**
     * Get graphs data for a specific academic year
     */
    public function getGraphData($academicYearId = null): array
    {
        $currentYear = null;

        // Si l'ID ressemble à une année académique (ex: "2026-2027"), chercher par academic_year
        if (is_string($academicYearId) && preg_match('/^\d{4}-\d{4}$/', $academicYearId)) {
            $currentYear = AcademicYear::where('academic_year', $academicYearId)->first();
 
            $academicYearId = $currentYear ? $currentYear->id : null;
        } else {
            // Sanitize l'ID pour PostgreSQL (convertir en int ou null)
            $academicYearId = DatabaseAdapter::sanitizeId($academicYearId);

            if ($academicYearId) {
                $currentYear = AcademicYear::find($academicYearId);
            } else {
                // Si pas d'ID valide, utiliser l'année courante
                $currentYear = AcademicYear::where('is_current', true)->first();
                $academicYearId = $currentYear ? $currentYear->id : null;
            }
        }

        $inscritsParFiliere = $this->getStudentsByDepartment($academicYearId);
        $inscritsParCycle = $this->getStudentsByCycle($academicYearId);
        $dossiersParstatus = $this->getStudentsBystatus($academicYearId);

        return [
            'inscritsParFiliere' => $inscritsParFiliere,
            'inscritsParCycle' => $inscritsParCycle,
            'dossiersParstatus' => $dossiersParstatus,
            'anneeAcademique' => $currentYear ? $currentYear->academic_year : null,
        ];
    }

    /**
     * Get students grouped by department
     */
    protected function getStudentsByDepartment($academicYearId = null): array
    {
        $departments = Department::all();

        if ($departments->isEmpty()) {
            return [['filiere' => 'Aucune filière', 'nombre' => 0]];
        }

        return $departments->map(function ($department) use ($academicYearId) {
            $query = PendingStudent::where('department_id', $department->id);

            if ($academicYearId && $academicYearId !== 'null') {
                $query->where('academic_year_id', $academicYearId);
            }

            return [
                'filiere' => $department->name ?? $department->libelle ?? 'N/A',
                'nombre' => $query->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Get students grouped by cycle
     */
    protected function getStudentsByCycle($academicYearId = null): array
    {
        $cycles = Cycle::all();

        if ($cycles->isEmpty()) {
            return [['cycle' => 'Aucun cycle', 'nombre' => 0]];
        }

        return $cycles->map(function ($cycle) use ($academicYearId) {
            $query = PendingStudent::whereHas('department', function($q) use ($cycle) {
                $q->where('cycle_id', $cycle->id);
            });

            if ($academicYearId && $academicYearId !== 'null') {
                $query->where('academic_year_id', $academicYearId);
            }

            return [
                'cycle' => $cycle->name ?? $cycle->libelle ?? 'N/A',
                'nombre' => $query->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Get students grouped by status
     */
    protected function getStudentsBystatus($academicYearId = null): array
    {
        $query = PendingStudent::select('status', DB::raw('count(*) as nombre'))
            ->groupBy('status');

        if ($academicYearId && $academicYearId !== 'null') {
            $query->where('academic_year_id', $academicYearId);
        }

        $statusData = $query->get();

        if ($statusData->isEmpty()) {
            return [['status' => 'Aucun dossier', 'nombre' => 0]];
        }

        return $statusData->map(function ($item) {
            return [
                'status' => $this->translatestatus($item->status),
                'nombre' => $item->nombre,
            ];
        })->values()->toArray();
    }

    /**
     * Translate status to French
     */
    protected function translatestatus(string $status): string
    {
        $translations = [
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'withdrawn' => 'Retiré',
        ];

        return $translations[$status] ?? $status;
    }
}

<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
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
        if (!$academicYearId) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentYear ? $currentYear->id : null;
        } else {
            $currentYear = AcademicYear::find($academicYearId);
        }

        $inscritsParFiliere = $this->getStudentsByDepartment($academicYearId);
        $inscritsParCycle = $this->getStudentsByCycle($academicYearId);
        $dossiersParStatut = $this->getStudentsByStatus($academicYearId);

        return [
            'inscritsParFiliere' => $inscritsParFiliere,
            'inscritsParCycle' => $inscritsParCycle,
            'dossiersParStatut' => $dossiersParStatut,
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
            $query = PendingStudent::where('department_id', $department->id)
                ->where('status', 'pending');
            
            if ($academicYearId) {
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
            })->where('status', 'approved');
            
            if ($academicYearId) {
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
    protected function getStudentsByStatus($academicYearId = null): array
    {
        $query = PendingStudent::select('status', DB::raw('count(*) as nombre'))
            ->groupBy('status');
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        $statusData = $query->get();
        
        if ($statusData->isEmpty()) {
            return [['statut' => 'Aucun dossier', 'nombre' => 0]];
        }

        return $statusData->map(function ($item) {
            return [
                'statut' => $this->translateStatus($item->status),
                'nombre' => $item->nombre,
            ];
        })->values()->toArray();
    }

    /**
     * Translate status to French
     */
    protected function translateStatus(string $status): string
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

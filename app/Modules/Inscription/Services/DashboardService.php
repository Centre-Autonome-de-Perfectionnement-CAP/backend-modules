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
        $anneeAcademique = $currentAcademicYear 
            ? $currentAcademicYear->libelle 
            : date('Y') . '-' . (date('Y') + 1);
        
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
    public function getGraphData(?string $academicYear = null): array
    {
        if (!$academicYear) {
            $currentYear = AcademicYear::where('is_current', true)->first();
            $academicYear = $currentYear 
                ? $currentYear->libelle 
                : date('Y') . '-' . (date('Y') + 1);
        }

        $inscritsParFiliere = $this->getStudentsByDepartment();
        $inscritsParCycle = $this->getStudentsByCycle();
        $dossiersParStatut = $this->getStudentsByStatus();

        return [
            'inscritsParFiliere' => $inscritsParFiliere,
            'inscritsParCycle' => $inscritsParCycle,
            'dossiersParStatut' => $dossiersParStatut,
            'anneeAcademique' => $academicYear,
        ];
    }

    /**
     * Get students grouped by department
     */
    protected function getStudentsByDepartment(): array
    {
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            return [['filiere' => 'Aucune filière', 'nombre' => 0]];
        }

        $totalApproved = PendingStudent::where('status', 'approved')->count();
        
        return $departments->map(function ($department) use ($totalApproved, $departments) {
            return [
                'filiere' => $department->name ?? $department->libelle ?? 'N/A',
                'nombre' => $departments->count() > 0 ? (int)($totalApproved / $departments->count()) : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Get students grouped by cycle
     */
    protected function getStudentsByCycle(): array
    {
        $cycles = Cycle::all();
        
        if ($cycles->isEmpty()) {
            return [['cycle' => 'Aucun cycle', 'nombre' => 0]];
        }

        $totalApproved = PendingStudent::where('status', 'approved')->count();
        
        return $cycles->map(function ($cycle) use ($totalApproved, $cycles) {
            return [
                'cycle' => $cycle->name ?? $cycle->libelle ?? 'N/A',
                'nombre' => $cycles->count() > 0 ? (int)($totalApproved / $cycles->count()) : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Get students grouped by status
     */
    protected function getStudentsByStatus(): array
    {
        $statusData = PendingStudent::select('status', DB::raw('count(*) as nombre'))
            ->groupBy('status')
            ->get();
        
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

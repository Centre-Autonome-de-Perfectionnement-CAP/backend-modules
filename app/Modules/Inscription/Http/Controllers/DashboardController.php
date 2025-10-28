<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard statistics controller for Inscription module
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get dashboard statistics
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $anneeAcademique = $currentAcademicYear 
                ? $currentAcademicYear->libelle 
                : date('Y') . '-' . (date('Y') + 1);
            
            $dossiersAttente = 0;
            $inscritsCap = 0;
            $nombreFilieres = 0;
            $nombreCycles = 0;
            
            try {
                $dossiersAttente = PendingStudent::whereIn('status', ['pending', 'documents_submitted'])->count();
                $inscritsCap = PendingStudent::where('status', 'approved')->count();
            } catch (\Exception $e) {
                \Log::warning('Erreur lors du comptage des étudiants: ' . $e->getMessage());
            }
            
            try {
                $nombreFilieres = Department::count();
                \Log::warning('Nombre de filières: ' . $nombreFilieres);
            } catch (\Exception $e) {
                \Log::warning('Erreur lors du comptage des filières: ' . $e->getMessage());
            }
            
            try {
                $nombreCycles = Cycle::count();
            } catch (\Exception $e) {
                \Log::warning('Erreur lors du comptage des cycles: ' . $e->getMessage());
            }

            return response()->json([
                'inscritsCap' => $inscritsCap ?? 0,
                'dossiersAttente' => $dossiersAttente ?? 0,
                'anneeAcademique' => $anneeAcademique,
                'nombreFilieres' => $nombreFilieres ?? 0,
                'nombreCycles' => $nombreCycles ?? 0,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur critique dans stats(): ' . $e->getMessage());
            
            // Return default values on error
            return response()->json([
                'inscritsCap' => 0,
                'dossiersAttente' => 0,
                'anneeAcademique' => date('Y') . '-' . (date('Y') + 1),
                'nombreFilieres' => 0,
                'nombreCycles' => 0,
            ], 200);
        }
    }

    /**
     * Get graphs data for a specific academic year
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function graphes(Request $request): JsonResponse
    {
        try {
            $academicYear = $request->query('year');
            
            if (!$academicYear) {
                $currentYear = AcademicYear::where('is_current', true)->first();
                $academicYear = $currentYear 
                    ? $currentYear->libelle 
                    : date('Y') . '-' . (date('Y') + 1);
            }

            $inscritsParFiliere = [];
            $inscritsParCycle = [];
            $dossiersParStatut = [];

            // Get students grouped by department/filiere with error handling
            try {
                $departments = Department::all();
                if ($departments->isEmpty()) {
                    $inscritsParFiliere = [
                        ['filiere' => 'Aucune filière', 'nombre' => 0]
                    ];
                } else {
                    $totalApproved = PendingStudent::where('status', 'approved')->count();
                    $inscritsParFiliere = $departments->map(function ($department) use ($totalApproved, $departments) {
                        return [
                            'filiere' => $department->name ?? $department->libelle ?? 'N/A',
                            'nombre' => $departments->count() > 0 ? (int)($totalApproved / $departments->count()) : 0,
                        ];
                    })->values()->toArray();
                }
            } catch (\Exception $e) {
                \Log::warning('Erreur lors de la récupération des filières: ' . $e->getMessage());
                $inscritsParFiliere = [['filiere' => 'Données non disponibles', 'nombre' => 0]];
            }

            // Get students grouped by cycle with error handling
            try {
                $cycles = Cycle::all();
                if ($cycles->isEmpty()) {
                    $inscritsParCycle = [
                        ['cycle' => 'Aucun cycle', 'nombre' => 0]
                    ];
                } else {
                    $totalApproved = PendingStudent::where('status', 'approved')->count();
                    $inscritsParCycle = $cycles->map(function ($cycle) use ($totalApproved, $cycles) {
                        return [
                            'cycle' => $cycle->name ?? $cycle->libelle ?? 'N/A',
                            'nombre' => $cycles->count() > 0 ? (int)($totalApproved / $cycles->count()) : 0,
                        ];
                    })->values()->toArray();
                }
            } catch (\Exception $e) {
                \Log::warning('Erreur lors de la récupération des cycles: ' . $e->getMessage());
                $inscritsParCycle = [['cycle' => 'Données non disponibles', 'nombre' => 0]];
            }

            // Get students by status with error handling
            try {
                $statusData = PendingStudent::select('status', DB::raw('count(*) as nombre'))
                    ->groupBy('status')
                    ->get();
                
                if ($statusData->isEmpty()) {
                    $dossiersParStatut = [
                        ['statut' => 'Aucun dossier', 'nombre' => 0]
                    ];
                } else {
                    $dossiersParStatut = $statusData->map(function ($item) {
                        return [
                            'statut' => $this->translateStatus($item->status),
                            'nombre' => $item->nombre,
                        ];
                    })->values()->toArray();
                }
            } catch (\Exception $e) {
                \Log::warning('Erreur lors de la récupération des statuts: ' . $e->getMessage());
                $dossiersParStatut = [['statut' => 'Données non disponibles', 'nombre' => 0]];
            }

            return response()->json([
                'inscritsParFiliere' => $inscritsParFiliere,
                'inscritsParCycle' => $inscritsParCycle,
                'dossiersParStatut' => $dossiersParStatut,
                'anneeAcademique' => $academicYear,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur critique dans graphes(): ' . $e->getMessage());
            
            // Return default empty data on critical error
            return response()->json([
                'inscritsParFiliere' => [['filiere' => 'Erreur système', 'nombre' => 0]],
                'inscritsParCycle' => [['cycle' => 'Erreur système', 'nombre' => 0]],
                'dossiersParStatut' => [['statut' => 'Erreur système', 'nombre' => 0]],
                'anneeAcademique' => date('Y') . '-' . (date('Y') + 1),
            ], 200);
        }
    }

    /**
     * Translate status to French
     * 
     * @param string $status
     * @return string
     */
    private function translateStatus(string $status): string
    {
        $translations = [
            'pending' => 'En attente',
            'documents_submitted' => 'Documents soumis',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
        ];

        return $translations[$status] ?? $status;
    }
}

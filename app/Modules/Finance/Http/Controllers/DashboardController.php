<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\DashboardService;
use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Récupère les statistiques du dashboard financier
     */
    public function getStats(Request $request)
    {
        try {
            $academicYearParam = $request->get('academic_year');
            $academicYear = null;
            
            if ($academicYearParam) {
                $academicYear = AcademicYear::where('libelle', $academicYearParam)->first();
            }
            $stats = $this->dashboardService->getFinancialStats($academicYear);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les paiements en attente de validation
     */
    public function getPendingPayments(Request $request)
    {
        try {
            $pendingPayments = $this->dashboardService->getPendingPayments();
            
            return response()->json([
                'success' => true,
                'data' => $pendingPayments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements en attente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
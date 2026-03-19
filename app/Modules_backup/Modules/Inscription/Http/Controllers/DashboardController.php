<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

/**
 * Dashboard statistics controller for Inscription module
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DashboardService $dashboardService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get dashboard statistics
     */
    public function stats(): JsonResponse
    {
        $stats = $this->dashboardService->getStats();
        
        return $this->successResponse($stats, 'Statistiques récupérées avec succès');
    }

    /**
     * Get graphs data for a specific academic year
     */
    public function graphes(Request $request): JsonResponse
    {
        $academicYear = $request->query('year');
        $graphData = $this->dashboardService->getGraphData($academicYear);
        
        return $this->successResponse($graphData, 'Données graphiques récupérées avec succès');
    }
}

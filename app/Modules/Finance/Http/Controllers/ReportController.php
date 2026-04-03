<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    use ApiResponse;

    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function exportPayments(Request $request)
    {
        $data = $this->reportService->exportPayments($request->all());
        
        $filename = 'paiements_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($data->first()));
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function getFinancialStatsByDepartment(Request $request)
    {
        $stats = $this->reportService->getFinancialStatsByDepartment(
            $request->input('academic_year_id'),
            $request->input('department_id')
        );

        return $this->successResponse($stats, 'Statistiques récupérées');
    }

    public function getRevenueByPeriod(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'group_by' => 'sometimes|in:day,month'
        ]);

        $revenue = $this->reportService->getRevenueByPeriod(
            $validated['start_date'],
            $validated['end_date'],
            $validated['group_by'] ?? 'month'
        );

        return $this->successResponse($revenue, 'Revenus récupérés');
    }
}

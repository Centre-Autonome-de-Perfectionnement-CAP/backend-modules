<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    public function exportPayments(array $filters)
    {
        $query = Paiement::with(['student', 'studentPendingStudent.pendingStudent.personalInformation']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        $payments = $query->get();

        $data = $payments->map(function ($payment) {
            $personalInfo = $payment->studentPendingStudent?->pendingStudent?->personalInformation;
            return [
                'Référence' => $payment->reference,
                'Matricule' => $payment->student_id_number,
                'Nom' => $personalInfo?->last_name ?? 'N/A',
                'Prénoms' => $personalInfo?->first_names ?? 'N/A',
                'Montant' => $payment->amount,
                'Date versement' => $payment->payment_date->format('d/m/Y'),
                'Statut' => $payment->status,
                'Motif' => $payment->purpose ?? 'N/A'
            ];
        });

        return $data;
    }

    public function getFinancialStatsByDepartment(int $academicYearId, ?int $departmentId = null)
    {
        $query = Paiement::query()
            ->join('student_pending_student', 'payments.student_pending_student_id', '=', 'student_pending_student.id')
            ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->join('academic_paths', 'student_pending_student.id', '=', 'academic_paths.student_pending_student_id')
            ->where('academic_paths.academic_year_id', $academicYearId);

        if ($departmentId) {
            $query->where('departments.id', $departmentId);
        }

        return $query->select(
            'departments.id',
            'departments.name',
            DB::raw('COUNT(DISTINCT payments.student_pending_student_id) as total_students'),
            DB::raw('SUM(CASE WHEN payments.status = "approved" THEN payments.amount ELSE 0 END) as total_collected'),
            DB::raw('COUNT(CASE WHEN payments.status = "pending" THEN 1 END) as pending_payments'),
            DB::raw('COUNT(CASE WHEN payments.status = "approved" THEN 1 END) as approved_payments')
        )
        ->groupBy('departments.id', 'departments.name')
        ->get();
    }

    public function getRevenueByPeriod(string $startDate, string $endDate, string $groupBy = 'month')
    {
        $dateFormat = $groupBy === 'day' ? '%Y-%m-%d' : '%Y-%m';

        return Paiement::where('status', 'approved')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(payment_date, '{$dateFormat}') as period"),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}

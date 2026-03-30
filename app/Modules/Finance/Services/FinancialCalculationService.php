<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Amount;
use App\Modules\Finance\Models\Paiement;
use App\Modules\Inscription\Models\AcademicPath;
use Carbon\Carbon;

class FinancialCalculationService
{
    private ExonerationService $exonerationService;

    public function __construct(ExonerationService $exonerationService)
    {
        $this->exonerationService = $exonerationService;
    }

    public function calculateTotalDue(int $studentPendingStudentId, int $academicYearId): array
    {
        $academicPath = AcademicPath::where('student_pending_student_id', $studentPendingStudentId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (!$academicPath) {
            return ['total' => 0, 'details' => []];
        }

        $exoneration = $this->exonerationService->getStudentExoneration($studentPendingStudentId, $academicYearId);
        $isExonerated = $academicPath->financial_status === 'Exonéré';

        $amounts = Amount::where('academic_year_id', $academicYearId)
            ->where('level', $academicPath->study_level)
            ->where('is_active', true)
            ->get();

        $details = [];
        $total = 0;

        foreach ($amounts as $amount) {
            $baseAmount = $isExonerated ? ($amount->sponsored_amount ?? 0) : $amount->amount;
            $finalAmount = $this->exonerationService->calculateExoneratedAmount($baseAmount, $exoneration);

            $penalty = $this->calculatePenalty($amount, $academicYearId);
            $finalAmount += $penalty;

            $details[] = [
                'type' => $amount->type,
                'libelle' => $amount->libelle,
                'base_amount' => $baseAmount,
                'exoneration' => $baseAmount - $finalAmount + $penalty,
                'penalty' => $penalty,
                'final_amount' => $finalAmount
            ];

            $total += $finalAmount;
        }

        return ['total' => $total, 'details' => $details];
    }

    private function calculatePenalty(Amount $amount, int $academicYearId): float
    {
        if (!$amount->penalty_active) {
            return 0;
        }

        $academicYear = \App\Modules\Inscription\Models\AcademicYear::find($academicYearId);
        if (!$academicYear || !$academicYear->start_date) {
            return 0;
        }

        $startDate = Carbon::parse($academicYear->start_date);
        $now = Carbon::now();
        $monthsLate = max(0, $now->diffInMonths($startDate) - 1);

        if ($monthsLate <= 0) {
            return 0;
        }

        if ($amount->penalty_type === 'percentage') {
            return $amount->amount * ($amount->penalty_amount / 100) * $monthsLate;
        }

        return $amount->penalty_amount * $monthsLate;
    }

    public function calculateBalance(int $studentPendingStudentId, int $academicYearId): array
    {
        // TEMPORAIRE : Montant fixe en dur pour solution immédiate
        // TODO : Remplacer par la logique dynamique basée sur la table amounts quand les tarifs seront configurés
        $totalDue = 425000; // Montant total à payer (inscription + frais de formation)

        /* ANCIENNE LOGIQUE (à réactiver plus tard) :
        $due = $this->calculateTotalDue($studentPendingStudentId, $academicYearId);
        $totalDue = $due['total'];
        */

        // Récupérer uniquement les paiements APPROUVÉS pour cet étudiant
        $paid = Paiement::where('student_pending_student_id', $studentPendingStudentId)
            ->where('status', 'approved')
            ->sum('amount');

        $balance = $totalDue - $paid;

        // Log pour débogage
        \Log::info('Financial Balance Calculation (HARDCODED)', [
            'student_pending_student_id' => $studentPendingStudentId,
            'academic_year_id' => $academicYearId,
            'total_due' => $totalDue,
            'total_paid' => $paid,
            'balance' => $balance,
        ]);

        return [
            'total_due' => $totalDue,
            'total_paid' => $paid,
            'balance' => $balance,
            'details' => [] // Vide pour l'instant car montant en dur
        ];
    }
}

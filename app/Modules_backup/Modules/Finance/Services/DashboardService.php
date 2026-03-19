<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Paiement;
use App\Modules\Finance\Models\Amount;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\AcademicYear;
use App\Services\DatabaseAdapter;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Récupère les statistiques financières pour le dashboard
     */
    public function getFinancialStats($academicYear = null)
    {
        // Si pas d'année académique spécifiée, prendre l'année académique courante
        if (!$academicYear) {
            $academicYear = AcademicYear::where('is_current', true)->first();
            if (!$academicYear) {
                // Fallback: prendre la dernière année académique
                $academicYear = AcademicYear::orderBy('year_start', 'desc')->first();
            }
        }

        // Paiements non validés en attente
        $pendingPayments = Paiement::pending()->count();

        // Frais encaissés dans l'année académique (somme des montants payés validés)
        $collectedAmount = Paiement::approved()
            ->whereBetween('payment_date', [
                $academicYear->year_start,
                $academicYear->year_end
            ])
            ->sum('amount');

        // Montant attendu (somme de la scolarité que doit payer chaque étudiant)
        $expectedAmount = $this->calculateExpectedAmount($academicYear);

        // Nombre total d'étudiants
        $totalStudents = Student::count();

        // Taux de recouvrement
        $recoveryRate = $expectedAmount > 0 ? ($collectedAmount / $expectedAmount) * 100 : 0;

        // Paiements par mois
        $monthlyPayments = $this->getMonthlyPayments($academicYear);

        // Répartition par status
        $paymentsBystatus = $this->getPaymentsBystatus();

        return [
            'pending_payments_count' => $pendingPayments,
            'collected_amount' => $collectedAmount,
            'expected_amount' => $expectedAmount,
            'total_students' => $totalStudents,
            'recovery_rate' => round($recoveryRate, 2),
            'monthly_payments' => $monthlyPayments,
            'payments_by_status' => $paymentsBystatus,
            'academic_year' => $academicYear ? $academicYear->libelle : 'Année courante'
        ];
    }

    /**
     * Calcule le montant attendu pour une année académique
     */
    private function calculateExpectedAmount($academicYear)
    {
        // Utilise directement l'objet AcademicYear passé en paramètre
        $academicYearRecord = $academicYear;

        if (!$academicYearRecord) {
            return 0;
        }

        if (!$academicYear) {
            return 0;
        }

        // Compte les étudiants inscrits cette année (via les dossiers)
        $studentsCount = Student::whereHas('studentPendingStudents.pendingStudent', function($query) use ($academicYearRecord) {
            $query->where('academic_year_id', $academicYearRecord->id);
        })->count();

        // Récupère le montant de base de la scolarité
        $baseAmount = Amount::where('type', 'scolarite')
            ->where('academic_year_id', $academicYearRecord->id)
            ->first();

        return $studentsCount * ($baseAmount ? $baseAmount->amount : 0);
    }

    /**
     * Récupère les paiements par mois pour une année académique
     */
    private function getMonthlyPayments($academicYear)
    {
        return Paiement::approved()
            ->whereBetween('payment_date', [
                $academicYear->year_start,
                $academicYear->year_end
            ])
            ->select(
                DB::raw(DatabaseAdapter::month('payment_date') . ' as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function($item) {
                return [
                    'month' => $item->month,
                    'month_name' => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'total' => $item->total
                ];
            });
    }

    /**
     * Récupère la répartition des paiements par status
     */
    private function getPaymentsBystatus()
    {
        return Paiement::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                    'label' => $this->getstatusLabel($item->status)
                ];
            });
    }

    /**
     * Récupère les paiements en attente de validation
     */
    public function getPendingPayments()
    {
        return Paiement::with(['student', 'studentPendingStudent'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Retourne le libellé d'un status
     */
    private function getstatusLabel($status)
    {
        $labels = [
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté'
        ];

        return $labels[$status] ?? $status;
    }
}

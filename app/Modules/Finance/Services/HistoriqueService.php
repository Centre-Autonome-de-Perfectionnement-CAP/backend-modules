<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Paiement;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\ClassGroup;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class HistoriqueService
{
    /**
     * Récupère l'historique financier par classe
     */
    public function getHistoriqueByClass($classId, $year = null)
    {
        $query = Student::with(['payments' => function($q) use ($year) {
            if ($year) {
                $q->whereYear('payment_date', $year);
            }
            $q->approved();
        }])->where('class_group_id', $classId);
        
        return $query->get()->map(function($student) {
            $totalPaid = $student->payments->sum('amount');
            $expectedAmount = $this->getExpectedAmountForStudent($student);
            
            return [
                'student' => $student,
                'total_paid' => $totalPaid,
                'expected_amount' => $expectedAmount,
                'remaining_amount' => $expectedAmount - $totalPaid,
                'payments_count' => $student->payments->count()
            ];
        });
    }

    /**
     * Récupère l'état financier détaillé d'un étudiant
     */
    public function getStudentFinancialState($studentId)
    {
        $student = Student::with([
            'personalInformation',
            'classGroup.department',
            'academicYear'
        ])->findOrFail($studentId);
        
        // Récupère tous les paiements groupés par année d'étude
        $paymentsByYear = Paiement::where('student_id_number', $student->student_id_number)
            ->approved()
            ->with(['academicYear'])
            ->orderBy('payment_date', 'asc')
            ->get()
            ->groupBy(function($payment) {
                return $payment->payment_date->format('Y');
            });
        
        $financialHistory = [];
        $runningBalance = 0;
        
        foreach ($paymentsByYear as $year => $payments) {
            $yearTotal = $payments->sum('amount');
            $expectedForYear = $this->getExpectedAmountForStudentByYear($student, $year);
            
            $runningBalance += $yearTotal;
            
            $financialHistory[] = [
                'year' => $year,
                'payments' => $payments->map(function($payment) use (&$runningBalance) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'payment_date' => $payment->payment_date,
                        'reference' => $payment->reference,
                        'receipt_url' => $payment->receipt_url,
                        'running_balance' => $runningBalance
                    ];
                }),
                'year_total' => $yearTotal,
                'expected_amount' => $expectedForYear,
                'remaining' => $expectedForYear - $yearTotal
            ];
        }
        
        return [
            'student' => $student,
            'financial_history' => $financialHistory,
            'total_paid' => $paymentsByYear->flatten()->sum('amount'),
            'total_expected' => array_sum(array_column($financialHistory, 'expected_amount')),
            'global_remaining' => array_sum(array_column($financialHistory, 'remaining'))
        ];
    }

    /**
     * Exporte l'état financier d'une classe
     */
    public function exportClassFinancialState($classId, $year = null)
    {
        $class = ClassGroup::with('department')->findOrFail($classId);
        $data = $this->getHistoriqueByClass($classId, $year);
        
        $exportData = $data->map(function($item) {
            return [
                'Matricule' => $item['student']->student_id_number,
                'Nom' => $item['student']->personalInformation->last_name ?? '',
                'Prénoms' => $item['student']->personalInformation->first_name ?? '',
                'Montant Payé' => $item['total_paid'],
                'Montant Attendu' => $item['expected_amount'],
                'Reste à Payer' => $item['remaining_amount'],
                'Nombre de Paiements' => $item['payments_count']
            ];
        });
        
        $filename = "etat_financier_{$class->name}_{$year}.xlsx";
        $path = storage_path("app/exports/{$filename}");
        
        // Utilise Excel pour créer le fichier
        Excel::store(new \App\Exports\ClassFinancialStateExport($exportData), "exports/{$filename}");
        
        return [
            'path' => $path,
            'filename' => $filename
        ];
    }

    /**
     * Calcule le montant attendu pour un étudiant
     */
    private function getExpectedAmountForStudent($student)
    {
        // Logique pour calculer le montant attendu basé sur la filière, année, etc.
        return 500000; // Exemple fixe, à adapter selon vos règles métier
    }

    /**
     * Calcule le montant attendu pour un étudiant par année
     */
    private function getExpectedAmountForStudentByYear($student, $year)
    {
        // Logique pour calculer le montant attendu par année
        return 500000; // Exemple fixe, à adapter selon vos règles métier
    }
}
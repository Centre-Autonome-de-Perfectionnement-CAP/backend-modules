<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\Paiement;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createFromPayment(Paiement $payment): Transaction
    {
        return Transaction::create([
            'student_pending_student_id' => $payment->student_pending_student_id,
            'payment_id' => $payment->id,
            'type' => 'payment',
            'amount' => $payment->amount,
            'reference' => $payment->reference,
            'description' => $payment->purpose ?? 'Paiement',
            'transaction_date' => $payment->payment_date,
            'status' => 'completed'
        ]);
    }

    public function getStudentTransactions(int $studentPendingStudentId)
    {
        return Transaction::where('student_pending_student_id', $studentPendingStudentId)
            ->with('payment')
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    public function getStudentBalance(int $studentPendingStudentId): float
    {
        $totalPaid = Transaction::where('student_pending_student_id', $studentPendingStudentId)
            ->where('type', 'payment')
            ->where('status', 'completed')
            ->sum('amount');

        $totalDue = Transaction::where('student_pending_student_id', $studentPendingStudentId)
            ->where('type', 'charge')
            ->where('status', 'completed')
            ->sum('amount');

        return $totalDue - $totalPaid;
    }
}

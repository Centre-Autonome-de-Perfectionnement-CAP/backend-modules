<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\FinancialCalculationService;
use App\Modules\Finance\Services\TransactionService;
use App\Modules\Finance\Models\Paiement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class StudentFinanceController extends Controller
{
    use ApiResponse;

    private FinancialCalculationService $calculationService;
    private TransactionService $transactionService;

    public function __construct(
        FinancialCalculationService $calculationService,
        TransactionService $transactionService
    ) {
        $this->calculationService = $calculationService;
        $this->transactionService = $transactionService;
    }

    public function getFinancialState(Request $request, int $studentPendingStudentId)
    {
        $academicYearId = $request->input('academic_year_id');
        
        $balance = $this->calculationService->calculateBalance($studentPendingStudentId, $academicYearId);
        $transactions = $this->transactionService->getStudentTransactions($studentPendingStudentId);
        
        $payments = Paiement::where('student_pending_student_id', $studentPendingStudentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse([
            'balance' => $balance,
            'transactions' => $transactions,
            'payments' => $payments
        ], 'État financier récupéré');
    }

    public function submitPayment(Request $request)
    {
        $validated = $request->validate([
            'matricule' => 'required|string',
            'department_id' => 'required|integer',
            'montant' => 'required|numeric|min:0',
            'reference' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $exists = Paiement::where('reference', $value)
                        ->whereIn('status', ['pending', 'approved'])
                        ->exists();
                    
                    if ($exists) {
                        $fail('Cette référence existe déjà dans le système.');
                    }
                },
            ],
            'numero_compte' => 'required|string',
            'date_versement' => 'required|date',
            'motif' => 'nullable|string',
            'email' => 'nullable|email',
            'contact' => 'nullable|string',
            'quittance' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $paiementService = app(\App\Modules\Finance\Services\PaiementService::class);
        $payment = $paiementService->create($validated, $request->file('quittance'));

        return $this->createdResponse($payment, 'Paiement soumis avec succès');
    }
}

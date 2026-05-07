<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    private TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function getStudentTransactions(Request $request, int $studentPendingStudentId)
    {
        $transactions = $this->transactionService->getStudentTransactions($studentPendingStudentId);
        return $this->successResponse($transactions, 'Transactions récupérées');
    }

    public function getStudentBalance(Request $request, int $studentPendingStudentId)
    {
        $balance = $this->transactionService->getStudentBalance($studentPendingStudentId);
        return $this->successResponse(['balance' => $balance], 'Solde récupéré');
    }
}

<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\ValidationService;
use App\Modules\Finance\Http\Requests\ValidatePaymentRequest;
use App\Modules\Finance\Http\Requests\RejectPaymentRequest;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    protected $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
<<<<<<< HEAD
     * Liste les paiements selon le statut
=======
     * Liste les paiements en attente de validation
>>>>>>> eea2b06 (draft)
     */
    public function getPendingPayments(Request $request)
    {
        try {
<<<<<<< HEAD
            $filters = $request->only(['search', 'page', 'per_page', 'status']);
=======
            $filters = $request->only(['search', 'page', 'per_page']);
>>>>>>> eea2b06 (draft)
            $payments = $this->validationService->getPendingPayments($filters);
            
            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valide un paiement
     */
    public function validatePayment(ValidatePaymentRequest $request, $paymentId)
    {
        try {
            $result = $this->validationService->validatePayment($paymentId, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement validé avec succès',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejette un paiement
     */
    public function rejectPayment(RejectPaymentRequest $request, $paymentId)
    {
        try {
            $result = $this->validationService->rejectPayment($paymentId, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Paiement rejeté avec succès',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharge une quittance
     */
    public function downloadReceipt($paymentId)
    {
        try {
            $receipt = $this->validationService->getReceiptFile($paymentId);
            
            return response()->download($receipt['path'], $receipt['filename']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement de la quittance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Paiement;
use App\Modules\Finance\Services\TransactionService;
use App\Modules\Core\Services\MailService;
use App\Modules\Finance\Jobs\SendPaymentNotificationJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ValidationService
{
    protected $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Récupère les paiements en attente de validation
     */
    public function getPendingPayments($filters = [])
    {
        $query = Paiement::with(['student', 'studentPendingStudent'])
            ->pending();
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('student_id_number', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%")
                  ->orWhereHas('student', function($sq) use ($search) {
                      $sq->where('first_name', 'like', "%$search%")
                        ->orWhere('last_name', 'like', "%$search%");
                  });
            });
        }
        
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Valide un paiement
     */
    public function validatePayment($paymentId, $data)
    {
        DB::beginTransaction();
        
        try {
            $payment = Paiement::findOrFail($paymentId);
            
            $payment->update([
                'status' => 'approved',
                'observation' => $data['observation'] ?? null,
                'validated_at' => now(),
                'validated_by' => auth()->id()
            ]);
            
            // Envoyer notification par email
            if ($payment->email) {
                SendPaymentNotificationJob::dispatch(
                    $payment->email,
                    'validation',
                    $payment->toArray()
                );
            }
            
            DB::commit();
            return $payment;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Rejette un paiement
     */
    public function rejectPayment($paymentId, $data)
    {
        DB::beginTransaction();
        
        try {
            $payment = Paiement::findOrFail($paymentId);
            
            $payment->update([
                'status' => 'rejected',
                'observation' => $data['motif'],
                'rejected_at' => now(),
                'rejected_by' => auth()->id()
            ]);
            
            // Envoyer notification par email avec motif de rejet
            if ($payment->email) {
                SendPaymentNotificationJob::dispatch(
                    $payment->email,
                    'rejection',
                    array_merge($payment->toArray(), ['rejection_reason' => $data['motif']])
                );
            }
            
            DB::commit();
            return $payment;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Récupère le fichier de quittance
     */
    public function getReceiptFile($paymentId)
    {
        $payment = Paiement::findOrFail($paymentId);
        
        if (!$payment->receipt_path || !Storage::exists($payment->receipt_path)) {
            throw new \Exception('Quittance non trouvée');
        }
        
        return [
            'path' => Storage::path($payment->receipt_path),
            'filename' => 'quittance_' . $payment->reference . '.' . pathinfo($payment->receipt_path, PATHINFO_EXTENSION)
        ];
    }
}
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
     * Récupère les paiements selon le statut
     */
    public function getPendingPayments($filters = [])
    {
        $query = Paiement::with([
            'student.pendingStudents.personalInformation', 
            'studentPendingStudent.pendingStudent.personalInformation',
            'legacyStudent'
        ]);
        
        // Filtrer par statut
        $status = $filters['status'] ?? 'pending';
        if ($status === 'approved') {
            $query->where('status', 'approved');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        } else {
            $query->where('status', 'pending');
        }
        
        if (!empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }
        
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    /**
     * Obtenir les totaux par statut (avec filtre de recherche éventuel)
     */
    public function getCounts(?string $search = null): array
    {
        $createBaseQuery = function() use ($search) {
            $q = Paiement::query();
            if (!empty($search)) {
                $this->applySearchFilter($q, $search);
            }
            return $q;
        };

        return [
            'pending' => $createBaseQuery()->where('status', 'pending')->count(),
            'approved' => $createBaseQuery()->where('status', 'approved')->count(),
            'rejected' => $createBaseQuery()->where('status', 'rejected')->count(),
            'all' => $createBaseQuery()->count(),
        ];
    }

    /**
     * Applique les critères de recherche textuelle globale sur la requête
     */
    protected function applySearchFilter($query, string $search): void
    {
        $search = trim($search);
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $query->where(function($q) use ($search, $terms) {
            $q->where('student_id_number', 'like', "%$search%")
              ->orWhere('reference', 'like', "%$search%")
              ->orWhere('account_number', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhere('contact', 'like', "%$search%")
              ->orWhere('purpose', 'like', "%$search%")
              ->orWhereHas('studentPendingStudent.pendingStudent.personalInformation', function($sq) use ($search, $terms) {
                  $sq->where(function($sub) use ($search, $terms) {
                      $sub->where('first_names', 'like', "%$search%")
                          ->orWhere('last_name', 'like', "%$search%");
                      if (count($terms) > 1) {
                          $sub->orWhere(function($multi) use ($terms) {
                              foreach ($terms as $term) {
                                  $multi->where(function($t) use ($term) {
                                      $t->where('first_names', 'like', "%$term%")
                                        ->orWhere('last_name', 'like', "%$term%");
                                  });
                              }
                          });
                      }
                  });
              })
              ->orWhereHas('student.pendingStudents.personalInformation', function($sq) use ($search, $terms) {
                  $sq->where(function($sub) use ($search, $terms) {
                      $sub->where('first_names', 'like', "%$search%")
                          ->orWhere('last_name', 'like', "%$search%");
                      if (count($terms) > 1) {
                          $sub->orWhere(function($multi) use ($terms) {
                              foreach ($terms as $term) {
                                  $multi->where(function($t) use ($term) {
                                      $t->where('first_names', 'like', "%$term%")
                                        ->orWhere('last_name', 'like', "%$term%");
                                  });
                              }
                          });
                      }
                  });
              })
              ->orWhereHas('legacyStudent', function($sq) use ($search, $terms) {
                  $sq->where(function($sub) use ($search, $terms) {
                      $sub->where('first_name', 'like', "%$search%")
                          ->orWhere('last_name', 'like', "%$search%");
                      if (count($terms) > 1) {
                          $sub->orWhere(function($multi) use ($terms) {
                              foreach ($terms as $term) {
                                  $multi->where(function($t) use ($term) {
                                      $t->where('first_name', 'like', "%$term%")
                                        ->orWhere('last_name', 'like', "%$term%");
                                  });
                              }
                          });
                      }
                  });
              });
        });
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
        
        // Nettoyer le nom du fichier en remplaçant les caractères interdits
        $cleanReference = str_replace(['/', '\\'], '_', $payment->reference);
        
        return [
            'path' => Storage::path($payment->receipt_path),
            'filename' => 'quittance_' . $cleanReference . '.' . pathinfo($payment->receipt_path, PATHINFO_EXTENSION)
        ];
    }
}
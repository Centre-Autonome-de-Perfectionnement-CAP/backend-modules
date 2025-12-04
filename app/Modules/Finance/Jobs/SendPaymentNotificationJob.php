<?php

namespace App\Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Core\Services\MailService;

class SendPaymentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $type;
    protected $paymentData;

    public function __construct($email, $type, $paymentData)
    {
        $this->email = $email;
        $this->type = $type;
        $this->paymentData = $paymentData;
    }

    public function handle(MailService $mailService)
    {
        if ($this->type === 'validation') {
            $subject = 'Paiement validé - CAP';
            $title = 'Paiement validé avec succès';
            $message = "Votre paiement de {$this->paymentData['amount']} FCFA avec la référence {$this->paymentData['reference']} a été validé avec succès.";
        } else {
            $subject = 'Paiement rejeté - CAP';
            $title = 'Paiement rejeté';
            $message = "Votre paiement de {$this->paymentData['amount']} FCFA avec la référence {$this->paymentData['reference']} a été rejeté.";
            
            if (isset($this->paymentData['rejection_reason'])) {
                $message .= "\n\nMotif: " . $this->paymentData['rejection_reason'];
            }
        }

        $mailService->sendNotification(
            $this->email,
            $subject,
            $title,
            $message,
            $this->paymentData
        );
    }
}
<?php

namespace App\Modules\Finance\Jobs;

use App\Modules\Core\Services\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Notification de validation / rejet d'un paiement (email + WhatsApp).
 *
 * ValidationService appelle directement notify() (publication immédiate de
 * deux jobs indépendants, un par canal). handle() reste pour les jobs déjà
 * présents en file sous ce nom.
 *
 * CORRECTIF : l'ancienne version passait la clé "message" à la vue
 * core::emails.notification. Laravel réserve $message dans les vues mail
 * (instance Illuminate\Mail\Message) : la vue plantait et AUCUN email de
 * validation / rejet de paiement n'était envoyé.
 */
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

    public function handle(Notifier $notifier): void
    {
        self::notify($notifier, $this->email, $this->type, (array) $this->paymentData);
    }

    public static function notify(Notifier $notifier, ?string $email, string $type, array $data): void
    {
        $amount    = number_format((float) ($data['amount'] ?? 0), 0, ',', ' ');
        $reference = (string) ($data['reference'] ?? '');
        $nom       = trim((string) ($data['student_full_name'] ?? '')) ?: 'Cher(e) étudiant(e)';
        $motif     = trim((string) ($data['rejection_reason'] ?? ''));

        if ($type === 'validation') {
            $subject = 'Paiement validé - CAP';
            $title   = 'Paiement validé avec succès';
            $body    = "Votre paiement de {$amount} FCFA (référence {$reference}) a été validé avec succès.";
            $wa      = \App\Modules\Core\Support\WhatsAppText::compose('Paiement validé', [
                "Bonjour {$nom},",
                'Votre paiement a été *validé*.',
                \App\Modules\Core\Support\WhatsAppText::fields(['Montant' => "{$amount} FCFA", 'Référence' => $reference]),
            ]);
        } else {
            $subject = 'Paiement rejeté - CAP';
            $title   = 'Paiement rejeté';
            $body    = "Votre paiement de {$amount} FCFA (référence {$reference}) a été rejeté."
                     . ($motif !== '' ? "\n\nMotif : {$motif}" : '');
            $wa      = \App\Modules\Core\Support\WhatsAppText::compose('Paiement non validé', [
                "Bonjour {$nom},",
                "Votre paiement n'a pas pu être validé.",
                \App\Modules\Core\Support\WhatsAppText::fields(['Montant' => "{$amount} FCFA", 'Référence' => $reference, 'Motif' => $motif]),
                '_Le détail vous a été envoyé par e-mail._',
            ]);
        }

        $notifier->email($email, $subject, 'core::emails.notification', [
            'title' => $title,
            'body'  => $body,
        ]);

        $phone = $notifier->phoneFromContacts($data['contact'] ?? null);
        $notifier->whatsapp($phone, $wa, "paiement-{$type}:{$reference}", 'Finance');
    }
}

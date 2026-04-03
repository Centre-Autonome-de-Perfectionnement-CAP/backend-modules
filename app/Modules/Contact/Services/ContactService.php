<?php

namespace App\Modules\Contact\Services;

use App\Modules\Contact\Mail\NewContactNotification;
use App\Modules\Contact\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    /**
     * Create a new contact message
     */
    public function createContact(array $data): Contact
    {
        $contact = Contact::create($data);

        // Log pour traçabilité
        Log::info('New contact message received', [
            'id' => $contact->id,
            'email' => $contact->email,
            'subject' => $contact->subject,
        ]);

        // Envoyer une notification email aux admins
        try {
            $this->notifyAdmins($contact);
        } catch (\Exception $e) {
            // Logger l'erreur sans bloquer la création du contact
            Log::error('Failed to send contact notification email', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage()
            ]);
        }

        return $contact;
    }

    /**
     * Notify admins about new contact message
     */
    protected function notifyAdmins(Contact $contact): void
    {
        $primaryEmail = config('mail.contact.to', 'contact@cap-epac.online');
        $ccEmail = config('mail.contact.cc', 'owomax@gmail.com');

        // Envoyer au destinataire principal avec copie
        $mail = Mail::to($primaryEmail);
        
        if ($ccEmail) {
            $mail->cc($ccEmail);
        }
        
        $mail->send(new NewContactNotification($contact));

        Log::info('Contact notification email sent', [
            'contact_id' => $contact->id,
            'to' => $primaryEmail,
            'cc' => $ccEmail
        ]);
    }
}

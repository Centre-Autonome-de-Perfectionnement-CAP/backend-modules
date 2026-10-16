<?php

namespace App\Modules\Contact\Services;

use App\Modules\Contact\Mail\NewContactNotification;
use App\Modules\Contact\Models\Contact;
use Illuminate\Support\Facades\Log;

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

        // Notifications asynchrones (email + WhatsApp) : la réponse au
        // visiteur ne dépend jamais du SMTP ni du bridge.
        try {
            $this->notifyAdmins($contact);
        } catch (\Throwable $e) {
            Log::error('Failed to queue contact notification', [
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

        $notifier = app(\App\Modules\Core\Services\Notifier::class);
        $notifier->mailable($primaryEmail, new NewContactNotification($contact), $ccEmail ? [$ccEmail] : []);

        $waNumbers = array_filter(array_map('trim', explode(',', (string) config('mail.contact.whatsapp', ''))));
        $preview = mb_strimwidth(trim(strip_tags((string) $contact->message)), 0, 500, '…');
        foreach ($waNumbers as $number) {
            $notifier->whatsapp(
                $number,
                "*CAP-EPAC — Nouveau message de contact*\nDe : {$contact->name} ({$contact->email})\nObjet : *{$contact->subject}*\n\n{$preview}",
                "contact:{$contact->id}",
                'Contact',
            );
        }

        Log::info('Contact notification queued', [
            'contact_id' => $contact->id,
            'to' => $primaryEmail,
            'cc' => $ccEmail,
            'whatsapp' => count($waNumbers),
        ]);
    }
}

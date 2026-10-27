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
     * Email au CAP (contenu complet), puis - UNIQUEMENT si l'email a été
     * accepté par le serveur SMTP - un WhatsApp au(x) responsable(s) de la
     * Formation Continue l'invitant à consulter la boîte mail. Le WhatsApp
     * ne contient JAMAIS le contenu du message (données du visiteur).
     */
    protected function notifyAdmins(Contact $contact): void
    {
        $primaryEmail = config('mail.contact.to', 'contact@cap-epac.online');
        $ccEmail = config('mail.contact.cc');

        $alerts = [];
        foreach ($this->formationContinueManagers() as $manager) {
            $alerts[] = [
                'phone'   => $manager->phone,
                'message' => \App\Modules\Core\Support\WhatsAppText::compose('Nouveau message de contact', [
                    "Bonjour {$manager->first_name},",
                    'Un nouveau message a été reçu via le formulaire de contact du site.',
                    "Merci de le traiter depuis la boîte mail *{$primaryEmail}*.",
                ]),
                'context' => "contact:{$contact->id}:{$manager->id}",
                'module'  => 'Contact',
            ];
        }

        app(\App\Modules\Core\Services\Notifier::class)->mailableThenWhatsApp(
            $primaryEmail,
            new NewContactNotification($contact),
            $alerts,
            $ccEmail ? [$ccEmail] : [],
        );

        if (empty($alerts)) {
            Log::warning('Contact : aucun responsable Formation Continue avec numéro WhatsApp - alerte non envoyée', [
                'contact_id' => $contact->id,
            ]);
        }

        Log::info('Contact notification queued', [
            'contact_id' => $contact->id,
            'to' => $primaryEmail,
            'cc' => $ccEmail,
            'whatsapp_alerts' => count($alerts),
        ]);
    }

    /** Responsable(s) de division « Formation Continue » joignables par WhatsApp. */
    private function formationContinueManagers(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->whereIn('r.slug', \App\Modules\Demandes\WorkflowConstants::roleSlugVariants('responsable-division'))
            ->where('u.chef_division_type', 'formation_continue')
            ->whereNotNull('u.phone')
            ->where('u.phone', '!=', '')
            ->select('u.id', 'u.first_name', 'u.phone')
            ->distinct()
            ->get();
    }
}

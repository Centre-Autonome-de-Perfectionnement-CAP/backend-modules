<?php

namespace App\Modules\Contact\Mail;

use App\Modules\Contact\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContactNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Contact $contact;

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouveau message de contact - ' . $this->contact->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new_contact_notification',
            with: [
                'contactName' => $this->contact->name,
                'contactEmail' => $this->contact->email,
                'contactSubject' => $this->contact->subject,
                'contactMessage' => $this->contact->message,
                'contactDate' => $this->contact->created_at->format('d/m/Y à H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

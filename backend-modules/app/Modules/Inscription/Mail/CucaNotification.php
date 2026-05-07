<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CucaNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $subject;
    public $content;

    /**
     * Create a new message instance.
     */
    public function __construct($student, $subject = null, $content = null)
    {
        $this->student = $student;
        $this->subject = $subject;
        $this->content = $content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject ?? 'Notification CUCA',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Assurez-vous que le chemin de la vue est correct et pointe vers votre fichier HTML
        // Même si le nom est "text", nous l'utilisons comme une vue HTML.
        return new Content(
            view: 'emails.cuca-notification-text', // C'est le nom de votre fichier .blade
            with: [
                // Nous allons rendre $student plus robuste pour éviter les erreurs si $student est null
                'student' => $this->student,
                // Assurez-vous que le contenu est bien une chaîne de caractères ou null
                'content' => $this->content,
                'subject_display' => $this->subject, // Pass subject for display in HTML header
            ],
        );
    }

    /**
     * Build the message (méthode alternative pour Laravel < 9)
     * Cette méthode est généralement utilisée pour les versions plus anciennes de Laravel.
     * Pour les nouvelles versions, la méthode content() est préférée.
     */
    public function build()
    {
        return $this->subject($this->subject ?? 'Notification CUCA')
                    // Nous utilisons 'view' pour indiquer que c'est une vue Blade HTML
                    ->view('emails.cuca-notification-text') // C'est le nom de votre fichier .blade
                    ->with([
                        'student' => $this->student,
                        'content' => $this->content,
                        'subject_display' => $this->subject,
                    ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
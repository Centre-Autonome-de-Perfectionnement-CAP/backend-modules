<?php

namespace App\Modules\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WhatsAppGroupInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;
    public $whatsappLink;
    public $departmentName;
    public $pdfPath;

    public function __construct($message, $whatsappLink, $departmentName, $pdfPath = null)
    {
        $this->messageContent = $message;
        $this->whatsappLink = $whatsappLink;
        $this->departmentName = $departmentName;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        $mail = $this->subject('Rejoignez le groupe WhatsApp - ' . $this->departmentName)
            ->view('core::emails.whatsapp-group-invitation');

        // Attacher le PDF si disponible
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as' => 'liste_etudiants_' . \Str::slug($this->departmentName) . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}

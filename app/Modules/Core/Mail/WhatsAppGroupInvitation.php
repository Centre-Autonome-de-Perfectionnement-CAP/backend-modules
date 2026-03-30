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

    public function __construct($message, $whatsappLink, $departmentName)
    {
        $this->messageContent = $message;
        $this->whatsappLink = $whatsappLink;
        $this->departmentName = $departmentName;
    }

    public function build()
    {
        return $this->subject('Rejoignez le groupe WhatsApp - ' . $this->departmentName)
            ->view('core::emails.whatsapp-group-invitation');
    }
}

<?php

namespace App\Modules\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportantInformationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $information;
    public $fileUrl;

    public function __construct($information, $fileUrl = null)
    {
        $this->information = $information;
        $this->fileUrl = $fileUrl;
    }

    public function build()
    {
        $mail = $this->subject('Information Importante - ' . $this->information['title'])
            ->view('Core::emails.important-information');

        // Attacher le fichier si disponible
        if ($this->fileUrl && file_exists($this->fileUrl)) {
            $mail->attach($this->fileUrl, [
                'as' => basename($this->fileUrl),
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}

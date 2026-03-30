<?php

namespace App\Modules\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportantInformationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $information;
    public $fileUrls; // Changé de $fileUrl à $fileUrls (array)

    public function __construct($information, $fileUrls = [])
    {
        $this->information = $information;
        $this->fileUrls = is_array($fileUrls) ? $fileUrls : [];
    }

    public function build()
    {
        $mail = $this->subject('Information Importante - ' . $this->information['title'])
            ->view('core::emails.important-information');

        // Attacher tous les fichiers si disponibles
        foreach ($this->fileUrls as $fileData) {
            if (isset($fileData['path']) && file_exists($fileData['path'])) {
                $mail->attach($fileData['path'], [
                    'as' => $fileData['name'] ?? basename($fileData['path']),
                    'mime' => 'application/pdf',
                ]);
            }
        }

        return $mail;
    }
}

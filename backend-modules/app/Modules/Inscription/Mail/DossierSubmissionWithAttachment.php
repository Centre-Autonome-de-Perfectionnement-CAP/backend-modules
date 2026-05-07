<?php

namespace App\Modules\Inscription\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DossierSubmissionWithAttachment extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $pdfPath;

    public function __construct(array $mailData, string $pdfPath)
    {
        $this->mailData = $mailData;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        return $this->subject('Confirmation de soumission - Fiche à joindre au dossier physique')
            ->view('core::emails.dossier_submission_with_attachment')
            ->attach($this->pdfPath, [
                'as' => 'Fiche_Confirmation_Inscription_' . $this->mailData['tracking_code'] . '.pdf',
                'mime' => 'application/pdf',
            ])
            ->with([
                'department' => $this->mailData['department'],
                'academic_year' => $this->mailData['academic_year'],
                'tracking_code' => $this->mailData['tracking_code'],
                'study_level' => $this->mailData['study_level'],
                'first_names' => $this->mailData['first_names'],
                'last_name' => $this->mailData['last_name'],
                'email' => $this->mailData['email'],
                'contacts' => $this->mailData['contacts'],
                'cycle_name' => $this->mailData['cycle_name'],
                'submission_datetime' => $this->mailData['submission_datetime'],
            ]);
    }
}

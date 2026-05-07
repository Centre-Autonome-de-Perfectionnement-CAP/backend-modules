<?php

namespace App\Modules\Inscription\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DossierSubmissionConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    public function build()
    {
        return $this->subject('Confirmation de soumission de dossier')
            ->view('emails.dossier_submission_confirmation')
            ->with([
                'department' => $this->mailData['department'],
                'academic_year' => $this->mailData['academic_year'],
                'tracking_code' => $this->mailData['tracking_code'],
                'study_level' => $this->mailData['study_level'],
                'first_names' => $this->mailData['first_names'],
                'email' => $this->mailData['email'],
                'contacts' => $this->mailData['contacts'],
                'cycle_name' => $this->mailData['cycle_name'],
            ]);
    }
}

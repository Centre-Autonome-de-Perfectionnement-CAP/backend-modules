<?php

namespace App\Modules\Inscription\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DossierCompletedConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    public function build()
    {
        return $this->subject('Confirmation de complément de dossier')
            ->view('emails.dossier_completed_confirmation')
            ->with([
                'department' => $this->mailData['department'],
                'academic_year' => $this->mailData['academic_year'],
                'tracking_code' => $this->mailData['tracking_code'],
                'study_level' => $this->mailData['study_level'],
                'first_names' => $this->mailData['first_names'] ?? 'Candidat(e)',
            ]);
    }
}

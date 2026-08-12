<?php

namespace App\Modules\Inscription\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformationCorrectionResult extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    public function build()
    {
        $subject = $this->mailData['status'] === 'approved'
            ? 'Vos informations ont été mises à jour'
            : 'Votre demande de correction a été refusée';

        return $this->subject($subject)
            ->view('emails.information_correction_result')
            ->with([
                'first_names'       => $this->mailData['first_names'],
                'student_id_number' => $this->mailData['student_id_number'],
                'status'            => $this->mailData['status'],
                'suggested_values'  => $this->mailData['suggested_values'],
                'rejection_reason'  => $this->mailData['rejection_reason'] ?? null,
            ]);
    }
}

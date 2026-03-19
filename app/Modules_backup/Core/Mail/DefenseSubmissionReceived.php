<?php

namespace App\Modules\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DefenseSubmissionReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $submission;

    public function __construct($submission)
    {
        $this->submission = $submission;
    }

    public function build()
    {
        return $this->subject('Accusé de réception - Soumission de votre dossier de soutenance')
                   ->view('core::emails.defense_submission_received')
                   ->with([
                       'studentName' => $this->submission->last_name.' '.$this->submission->first_names,
                       'thesisTitle' => $this->submission->thesis_title,
                       'submissionDate' => $this->submission->created_at->format('d/m/Y H:i'),
                       'defenseType' => $this->submission->defense_type,
                   ]);
    }
}

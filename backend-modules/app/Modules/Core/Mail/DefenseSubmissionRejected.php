<?php

namespace App\Modules\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DefenseSubmissionRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $submission;
    public $reason;

    public function __construct($submission, $reason)
    {
        $this->submission = $submission;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('Votre dossier de soutenance a été rejeté')
                   ->view('core::emails.defense_submission_rejected')
                   ->with([
                       'studentName' => $this->submission->last_name.' '.$this->submission->first_names,
                       'thesisTitle' => $this->submission->thesis_title,
                       'defenseType' => $this->submission->defense_type,
                       'rejectionReason' => $this->reason,
                   ]);
    }
}

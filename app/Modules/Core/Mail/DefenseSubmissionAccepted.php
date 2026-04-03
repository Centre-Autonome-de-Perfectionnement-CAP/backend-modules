<?php

namespace App\Modules\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DefenseSubmissionAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public $submission;

    public function __construct($submission)
    {
        $this->submission = $submission;
    }

    public function build()
    {
        return $this->subject('Votre dossier de soutenance a été accepté')
                   ->view('core::emails.defense_submission_accepted')
                   ->with([
                       'studentName' => $this->submission->last_name.' '.$this->submission->first_names,
                       'thesisTitle' => $this->submission->thesis_title,
                       'defenseType' => $this->submission->defense_type,
                   ]);
    }
}

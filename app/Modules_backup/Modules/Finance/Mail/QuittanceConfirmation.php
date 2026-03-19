<?php

namespace App\Modules\Finance\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuittanceConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    public function build()
    {
        return $this->subject('Confirmation de réception de quittance')
            ->view('emails.quittance_confirmation')
            ->with([
                'reference' => $this->mailData['reference'],
                'matricule' => $this->mailData['matricule'],
                'montant' => $this->mailData['montant'],
                'numero_compte' => $this->mailData['numero_compte'],
                'date_versement' => $this->mailData['date_versement'],
                'motif' => $this->mailData['motif'],
                'prenoms' => $this->mailData['prenoms'] ?? 'Étudiant(e)',
                'nom' => $this->mailData['nom'] ?? '',
            ]);
    }
}

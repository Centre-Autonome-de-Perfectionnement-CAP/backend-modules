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
        // Envoi en file : le chemin est relatif au disque "local" (résolu au
        // moment de l'envoi, indépendant du dossier de release). Un chemin
        // absolu reste accepté (compatibilité).
        $absolute = (str_starts_with($this->pdfPath, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $this->pdfPath))
            ? $this->pdfPath
            : \Illuminate\Support\Facades\Storage::disk('local')->path($this->pdfPath);

        $mail = $this->subject('Confirmation de soumission - Fiche à joindre au dossier physique')
            ->view('core::emails.dossier_submission_with_attachment');

        if (is_file($absolute)) {
            $mail->attach($absolute, [
                'as' => 'Fiche_Confirmation_Inscription_' . $this->mailData['tracking_code'] . '.pdf',
                'mime' => 'application/pdf',
            ]);
        } else {
            \Illuminate\Support\Facades\Log::warning('Fiche de confirmation introuvable - email envoyé sans pièce jointe', [
                'tracking_code' => $this->mailData['tracking_code'] ?? null,
            ]);
        }

        return $mail->with([
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

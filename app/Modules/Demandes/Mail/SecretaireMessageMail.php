<?php

namespace App\Modules\Demandes\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Message libre envoyé par la secrétaire à un demandeur, depuis le dashboard
 * (bouton "Contacter le demandeur"). Envoyé de façon SYNCHRONE (pas de queue) :
 * l'action est déclenchée manuellement par la secrétaire, qui attend un
 * retour immédiat (succès/échec) dans le modal — voir ContactDemandeurService.
 *
 * @param array<int, array{path: string, name: string}> $attachments
 *   Chaque pièce jointe est déjà stockée sur le disque 'public' au moment de
 *   la construction (voir DocumentStorageService::storeSecretaireFile) —
 *   ce Mailable ne fait que les relier via Attachment::fromStorageDisk().
 */
class SecretaireMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reference,
        public string $typeLabel,
        public string $nomDemandeur,
        public string $message,
        public string $secretaireName,
        public array $attachments = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "CAP-EPAC — Message concernant votre dossier {$this->reference}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.secretaire-message',
            with: [
                'reference'      => $this->reference,
                'typeLabel'      => $this->typeLabel,
                'nomDemandeur'   => $this->nomDemandeur,
                'message'        => $this->message,
                'secretaireName' => $this->secretaireName,
            ],
        );
    }

    public function attachments(): array
    {
        return array_map(
            fn (array $a) => Attachment::fromStorageDisk('public', $a['path'])->as($a['name']),
            $this->attachments,
        );
    }
}

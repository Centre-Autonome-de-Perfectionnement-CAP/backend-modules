<?php

namespace App\Modules\Demandes\Services;

use App\Exceptions\BusinessException;
use App\Modules\Demandes\Mail\SecretaireMessageMail;
use App\Modules\Demandes\Models\DocumentRequest;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi d'un message libre (texte + pièces jointes) de la secrétaire vers
 * le demandeur d'un dossier, par WhatsApp ET email.
 *
 * ENVOI SYNCHRONE (pas de queue) : action interactive déclenchée par un clic
 * secrétaire, qui attend un retour immédiat succès/échec par canal — pas de
 * SendNotificationJob ici, contrairement au reste du module.
 *
 * Chaque canal est indépendant : un échec WhatsApp n'empêche pas l'envoi
 * email et vice-versa. Le résultat retourné donne le motif exact de chaque
 * échec pour affichage direct au frontend (pas de message générique).
 */
class ContactDemandeurService
{
    public function __construct(
        private readonly WhatsAppService        $whatsApp,
        private readonly DocumentStorageService  $storageService,
    ) {}

    /**
     * @return array{
     *   whatsapp: array{attempted: bool, success: bool, reason: ?string},
     *   email:    array{attempted: bool, success: bool, reason: ?string},
     * }
     * @throws BusinessException si aucun canal n'est joignable (ni whatsapp ni email connus)
     */
    public function send(DocumentRequest $demande, string $message, array $attachmentFiles, Request $request): array
    {
        $message = trim(strip_tags($message));
        if ($message === '') {
            throw new BusinessException('Le message ne peut pas être vide.', 'EMPTY_MESSAGE', 422);
        }

        if (empty($demande->demandeur_whatsapp) && empty($demande->email)) {
            throw new BusinessException(
                "Ce dossier n'a ni numéro WhatsApp ni email enregistré — impossible de contacter le demandeur.",
                'NO_CONTACT_INFO',
                422,
            );
        }

        $nomDemandeur = trim(($demande->last_name ?? '') . ' ' . ($demande->first_names ?? '')) ?: 'Demandeur';
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $secretaire   = $request->user();
        $secretaireName = trim($secretaire?->name ?? $secretaire?->first_name . ' ' . $secretaire?->last_name) ?: 'Le secrétariat';

        // ── Stockage des pièces jointes (une seule fois, réutilisées pour les 2 canaux) ──
        $stored = [];
        foreach ($attachmentFiles as $file) {
            if (!$file || !$file->isValid()) continue;
            $fileId = uniqid('msg_');
            $path   = $this->storageService->storeSecretaireFile($demande->type, $demande->reference, $file, $fileId);
            $stored[] = ['path' => $path, 'name' => $file->getClientOriginalName()];
        }

        $result = [
            'whatsapp' => ['attempted' => false, 'success' => false, 'reason' => null],
            'email'    => ['attempted' => false, 'success' => false, 'reason' => null],
        ];

        // ── WhatsApp ──────────────────────────────────────────────────────────
        if (!empty($demande->demandeur_whatsapp)) {
            $result['whatsapp']['attempted'] = true;
            $result['whatsapp'] = $this->sendWhatsApp($demande, $nomDemandeur, $message, $secretaireName, $stored);
        }

        // ── Email ─────────────────────────────────────────────────────────────
        if (!empty($demande->email)) {
            $result['email']['attempted'] = true;
            $result['email'] = $this->sendEmail($demande, $nomDemandeur, $typeLabel, $message, $secretaireName, $stored);
        }

        return $result;
    }

    private function sendWhatsApp(
        DocumentRequest $demande,
        string $nomDemandeur,
        string $message,
        string $secretaireName,
        array $stored,
    ): array {
        if (!$this->whatsApp->isConnected()) {
            Log::warning('[ContactDemandeur] Bridge WhatsApp injoignable', ['reference' => $demande->reference]);
            return [
                'attempted' => true,
                'success'   => false,
                'reason'    => "Le service WhatsApp est actuellement déconnecté ou injoignable. Vérifiez la connexion WhatsApp dans l'onglet Administration.",
            ];
        }

        $waMessage = implode("\n\n", array_filter([
            "*CAP-EPAC — Message du secrétariat*",
            "Bonjour {$nomDemandeur},",
            $message,
            "— {$secretaireName}, Secrétariat CAP-EPAC",
        ]));

        $context = "message-secretariat:{$demande->reference}";
        $ok = $this->whatsApp->send($demande->demandeur_whatsapp, $waMessage, $context);

        if (!$ok) {
            return [
                'attempted' => true,
                'success'   => false,
                'reason'    => "Échec de l'envoi WhatsApp : numéro invalide ({$demande->demandeur_whatsapp}) ou service indisponible. Voir les journaux pour le détail exact.",
            ];
        }

        // Pièces jointes : envoyées séparément, un échec de fichier ne fait pas
        // échouer le message texte déjà parti — juste consigné dans les logs.
        foreach ($stored as $att) {
            $fileOk = $this->whatsApp->sendFile(
                phone:    $demande->demandeur_whatsapp,
                disk:     'public',
                path:     $att['path'],
                fileName: $att['name'],
                caption:  "Pièce jointe — dossier {$demande->reference}",
                context:  $context,
            );
            if (!$fileOk) {
                Log::warning('[ContactDemandeur] Échec envoi pièce jointe WhatsApp', [
                    'reference' => $demande->reference,
                    'file'      => $att['name'],
                ]);
            }
        }

        return ['attempted' => true, 'success' => true, 'reason' => null];
    }

    private function sendEmail(
        DocumentRequest $demande,
        string $nomDemandeur,
        string $typeLabel,
        string $message,
        string $secretaireName,
        array $stored,
    ): array {
        try {
            Mail::to($demande->email)->send(new SecretaireMessageMail(
                reference:      $demande->reference,
                typeLabel:      $typeLabel,
                nomDemandeur:   $nomDemandeur,
                message:        $message,
                secretaireName: $secretaireName,
                attachments:    $stored,
            ));

            return ['attempted' => true, 'success' => true, 'reason' => null];
        } catch (\Throwable $e) {
            Log::error('[ContactDemandeur] Échec envoi email', [
                'reference' => $demande->reference,
                'email'     => $demande->email,
                'error'     => $e->getMessage(),
            ]);

            return [
                'attempted' => true,
                'success'   => false,
                'reason'    => "Échec de l'envoi email : " . $e->getMessage(),
            ];
        }
    }
}

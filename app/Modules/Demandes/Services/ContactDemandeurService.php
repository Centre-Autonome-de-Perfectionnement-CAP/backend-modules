<?php

namespace App\Modules\Demandes\Services;

use App\Exceptions\BusinessException;
use App\Modules\Demandes\Models\DocumentRequest;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Envoi d'un message libre (texte + pièces jointes) de la secrétaire vers
 * le demandeur d'un dossier, par WhatsApp ET email.
 *
 * REFONTE (13/10/2026) — ENVOI ASYNCHRONE :
 *   L'ancienne version envoyait WhatsApp (texte + chaque fichier) de façon
 *   SYNCHRONE dans la requête HTTP : jusqu'à 30 s pour le texte + 60 s par
 *   pièce jointe. Au-delà du timeout du serveur web, le navigateur recevait
 *   une erreur alors que le WhatsApp était bien parti (bug « parfois ça
 *   marche, parfois non, mais le message part »). L'email, lui, était
 *   désactivé et ne pouvait de toute façon pas partir (Mailable invalide :
 *   propriété $attachments typée en conflit avec Mailable + variable de vue
 *   'message' réservée par Laravel).
 *
 *   Désormais, chaque canal est mis en file d'envoi (SendNotificationJob),
 *   indépendamment l'un de l'autre, avec retry automatique 24 h en cas
 *   d'incident (bridge déconnecté, SMTP indisponible). La réponse HTTP est
 *   immédiate.
 */
class ContactDemandeurService
{
    public function __construct(
        private readonly DocumentStorageService        $storageService,
        private readonly NotificationService           $notificationService,
        private readonly WhatsAppService               $whatsApp,
        private readonly DocumentRequestHistoryService $historyService,
    ) {}

    /**
     * @param  \Illuminate\Http\UploadedFile[]  $attachmentFiles
     * @return array{
     *   whatsapp: array{attempted: bool, success: bool, queued: bool, reason: ?string},
     *   email:    array{attempted: bool, success: bool, queued: bool, reason: ?string},
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

        // last_name/first_names ne sont pas des colonnes de document_requests :
        // jointure personal_information.
        // Inscrit OU ancien étudiant (jointures gauches, cf. Requester).
        $infoQuery = DB::table('document_requests as dr');
        \App\Modules\Demandes\Support\Requester::join($infoQuery, 'dr', false);
        $info = $infoQuery
            ->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id')
            ->where('dr.id', $demande->id)
            ->select(
                DB::raw(\App\Modules\Demandes\Support\Requester::LAST_NAME . ' as last_name'),
                DB::raw(\App\Modules\Demandes\Support\Requester::FIRST_NAMES . ' as first_names'),
                DB::raw('COALESCE(ay.academic_year, dr.bulletin_year) as academic_year'),
            )
            ->first();

        $nomDemandeur   = trim(($info->first_names ?? '') . ' ' . ($info->last_name ?? '')) ?: 'Demandeur';
        $typeLabel      = WorkflowConstants::typeLabel($demande->type, $info->academic_year ?? null);
        $secretaire     = $request->user();
        $secretaireName = trim((string) ($secretaire?->first_name ?? '') . ' ' . (string) ($secretaire?->last_name ?? ''))
            ?: (trim((string) ($secretaire?->name ?? '')) ?: 'Le secrétariat');

        // ── Stockage des pièces jointes (une seule fois, réutilisées par les 2 canaux) ──
        $stored = [];
        foreach ($attachmentFiles as $file) {
            if (!$file || !$file->isValid()) continue;
            $fileId   = uniqid('msg_');
            $path     = $this->storageService->storeSecretaireFile($demande->type, $demande->reference, $file, $fileId);
            $stored[] = ['path' => $path, 'name' => $this->safeFileName($file->getClientOriginalName())];
        }

        $queued = $this->notificationService->sendMessageDemandeur(
            demande:        $demande,
            nomDemandeur:   $nomDemandeur,
            typeLabel:      $typeLabel,
            message:        $message,
            secretaireName: $secretaireName,
            attachments:    $stored,
        );

        // Traçabilité dans l'historique du dossier (jamais bloquant).
        try {
            $this->historyService->recordMail($demande->id, 'Message du secrétariat au demandeur');
        } catch (\Throwable $e) {
            Log::warning('[ContactDemandeur] Historique non enregistré', [
                'reference' => $demande->reference,
                'error'     => $e->getMessage(),
            ]);
        }

        Log::info('[ContactDemandeur] Message mis en file d\'envoi', [
            'reference'   => $demande->reference,
            'whatsapp'    => $queued['whatsapp'],
            'email'       => $queued['email'],
            'attachments' => count($stored),
        ]);

        // Information (non bloquante) : bridge actuellement déconnecté →
        // le message partira automatiquement à la reconnexion.
        $waReason = null;
        if ($queued['whatsapp'] && !$this->whatsApp->isConnected()) {
            $waReason = "WhatsApp est momentanément déconnecté : le message partira automatiquement dès la reconnexion.";
        }

        return [
            'whatsapp' => [
                'attempted' => $queued['whatsapp'],
                'success'   => $queued['whatsapp'],
                'queued'    => $queued['whatsapp'],
                'reason'    => $waReason,
            ],
            'email' => [
                'attempted' => $queued['email'],
                'success'   => $queued['email'],
                'queued'    => $queued['email'],
                'reason'    => null,
            ],
        ];
    }

    /**
     * Nom de fichier affiché au destinataire : sans chemin ni caractères de contrôle.
     */
    private function safeFileName(?string $name): string
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name);

        return $name !== '' ? mb_substr($name, 0, 150) : 'piece-jointe';
    }
}

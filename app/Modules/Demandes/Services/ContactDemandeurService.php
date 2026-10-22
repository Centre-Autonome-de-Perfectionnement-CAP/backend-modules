<?php

namespace App\Modules\Demandes\Services;

use App\Exceptions\BusinessException;
use App\Modules\Demandes\Models\DocumentRequest;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Modules\Demandes\Jobs\SendNotificationJob;
use App\Modules\WhatsApp\Services\WhatsAppBridgeClient;

/**
 * Envoi d'un message libre (texte + pièces jointes) de la secrétaire vers
 * le demandeur d'un dossier, par WhatsApp ET email.
 *
 * REFONTE (13/10/2026) - ENVOI ASYNCHRONE :
 *   L'ancienne version envoyait WhatsApp (texte + chaque fichier) de façon
 *   SYNCHRONE dans la requête HTTP : jusqu'à 30 s pour le texte + 60 s par
 *   pièce jointe. Au-delà du timeout du serveur web, le navigateur recevait
 *   une erreur alors que le WhatsApp était bien parti (bug « parfois ça
 *   marche, parfois non, mais le message part »). L'email, lui, était
 *   désactivé et ne pouvait de toute façon pas partir (Mailable invalide :
 *   propriété $attachments typée en conflit avec Mailable + variable de vue
 *   'message' réservée par Laravel).
 *
 *   (16/10/2026) Retour à l'envoi SYNCHRONE à la demande du CAP : la
 *   secrétaire doit savoir si le message est réellement parti. Texte
 *   WhatsApp et email sont envoyés pendant la requête et leur résultat réel
 *   est renvoyé. Les pièces jointes WhatsApp sont bornées dans le temps
 *   (SYNC_BUDGET_SECONDS) pour ne jamais dépasser le délai du serveur web ;
 *   le reste part juste après la réponse avec relance automatique.
 */
class ContactDemandeurService
{
    public function __construct(
        private readonly DocumentStorageService        $storageService,
        private readonly NotificationService           $notificationService,
        private readonly WhatsAppService               $whatsApp,
        private readonly DocumentRequestHistoryService $historyService,
        private readonly WhatsAppBridgeClient          $bridge,
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
                "Ce dossier n'a ni numéro WhatsApp ni email enregistré - impossible de contacter le demandeur.",
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

        // ── Envoi SYNCHRONE : la secrétaire voit le résultat réel ─────────────
        $context   = "message-secretariat:{$demande->reference}";
        $startedAt = microtime(true);

        $whatsapp = $this->sendWhatsAppNow($demande, $nomDemandeur, $message, $secretaireName, $stored, $context, $startedAt);
        $email    = $this->sendEmailNow($demande, $nomDemandeur, $typeLabel, $message, $secretaireName, $stored);

        // Traçabilité dans l'historique du dossier (jamais bloquant), seulement
        // si au moins un canal a réellement abouti.
        if ($whatsapp['success'] || $email['success']) {
            try {
                $this->historyService->recordMail($demande->id, 'Message du secrétariat au demandeur');
            } catch (\Throwable $e) {
                Log::warning('[ContactDemandeur] Historique non enregistré', [
                    'reference' => $demande->reference,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info('[ContactDemandeur] Message au demandeur', [
            'reference'   => $demande->reference,
            'whatsapp'    => $whatsapp,
            'email'       => $email,
            'attachments' => count($stored),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return ['whatsapp' => $whatsapp, 'email' => $email];
    }

    /**
     * Durée maximale passée à envoyer les pièces jointes WhatsApp pendant la
     * requête (le serveur web coupe vers 60 s). Les fichiers restants partent
     * juste après la réponse, avec relance automatique en cas d'échec.
     */
    private const SYNC_BUDGET_SECONDS = 35;

    /** @return array{attempted: bool, success: bool, queued: bool, reason: ?string} */
    private function sendWhatsAppNow(
        DocumentRequest $demande,
        string $nomDemandeur,
        string $message,
        string $secretaireName,
        array $stored,
        string $context,
        float $startedAt,
    ): array {
        $phone = (string) ($demande->demandeur_whatsapp ?? '');
        if ($phone === '') {
            return ['attempted' => false, 'success' => false, 'queued' => false, 'reason' => null];
        }

        if (!$this->bridge->isConnected()) {
            return $this->failed("WhatsApp du CAP déconnecté : le message n'est pas parti. Reconnectez WhatsApp puis renvoyez.");
        }

        $res = $this->bridge->sendDetailed(
            $phone,
            $this->whatsApp->templateMessageSecretariat($nomDemandeur, $message, $secretaireName),
            $context,
            'Demandes',
        );
        if (!$res['ok']) {
            return $this->failed($this->explainWhatsAppError($res));
        }

        // Pièces jointes : envoyées tant que le délai le permet, le reste
        // part juste après la réponse (avec relance automatique).
        $failed = []; $later = 0;
        foreach ($stored as $att) {
            if ((microtime(true) - $startedAt) >= self::SYNC_BUDGET_SECONDS) {
                SendNotificationJob::send('whatsapp_file', [
                    'phone' => $phone, 'disk' => 'public', 'path' => $att['path'], 'file_name' => $att['name'],
                    'caption' => "_Pièce jointe du dossier {$demande->reference}_", 'context' => $context, 'module' => 'Demandes',
                ]);
                $later++;
                continue;
            }

            $fileRes = $this->bridge->sendFileDetailed(
                $phone, 'public', $att['path'], $att['name'],
                "Pièce jointe - dossier {$demande->reference}", $context, 'Demandes',
            );
            if (!$fileRes['ok']) {
                $failed[] = $att['name'];
            }
        }

        $notes = [];
        if ($failed) {
            $notes[] = 'Pièce(s) jointe(s) non envoyée(s) par WhatsApp : ' . implode(', ', $failed) . '.';
        }
        if ($later) {
            $notes[] = "{$later} pièce(s) jointe(s) en cours d'envoi (relance automatique en cas d'échec).";
        }

        return [
            'attempted' => true,
            'success'   => true,
            'queued'    => false,
            'reason'    => $notes ? implode(' ', $notes) : null,
        ];
    }

    /** @return array{attempted: bool, success: bool, queued: bool, reason: ?string} */
    private function sendEmailNow(
        DocumentRequest $demande,
        string $nomDemandeur,
        string $typeLabel,
        string $message,
        string $secretaireName,
        array $stored,
    ): array {
        $to = trim((string) ($demande->email ?? ''));
        if ($to === '') {
            return ['attempted' => false, 'success' => false, 'queued' => false, 'reason' => null];
        }
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return $this->failed("Adresse email du demandeur invalide ({$to}).");
        }

        try {
            Mail::send('core::emails.secretaire-message', [
                'reference'      => $demande->reference,
                'typeLabel'      => $typeLabel,
                'nomDemandeur'   => $nomDemandeur,
                // 'message' est réservé par Laravel dans les vues mail.
                'messageBody'    => $message,
                'secretaireName' => $secretaireName,
            ], function ($m) use ($to, $nomDemandeur, $demande, $stored) {
                $m->to($to, $nomDemandeur)
                  ->subject("CAP-EPAC - Message concernant votre dossier {$demande->reference}");
                foreach ($stored as $att) {
                    if (Storage::disk('public')->exists($att['path'])) {
                        $m->attachFromStorageDisk('public', $att['path'], $att['name']);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('[ContactDemandeur] Email non envoyé', [
                'reference' => $demande->reference, 'to' => $to, 'error' => $e->getMessage(),
            ]);
            return $this->failed("Le serveur email a refusé l'envoi. Réessayez dans quelques minutes.");
        }

        return ['attempted' => true, 'success' => true, 'queued' => false, 'reason' => null];
    }

    /** @return array{attempted: bool, success: bool, queued: bool, reason: ?string} */
    private function failed(string $reason): array
    {
        return ['attempted' => true, 'success' => false, 'queued' => false, 'reason' => $reason];
    }

    /** @param array{ok: bool, retryable: bool, http: int, error: ?string} $res */
    private function explainWhatsAppError(array $res): string
    {
        $error = (string) ($res['error'] ?? '');

        return match (true) {
            str_contains(mb_strtolower($error), 'invalide')
                => "Numéro WhatsApp du demandeur invalide : le message n'est pas parti.",
            $res['retryable']
                => "WhatsApp momentanément indisponible : le message n'est pas parti. Réessayez dans quelques minutes.",
            default
                => "Le message WhatsApp n'a pas pu être remis (numéro sans WhatsApp ou refusé).",
        };
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

<?php

namespace App\Modules\Attestation\Services;

use App\Modules\Core\Services\WhatsAppBridgeClient;
use Illuminate\Support\Facades\Log;

/**
 * Service de notifications WhatsApp pour le module Attestation.
 *
 * Envoie des messages WhatsApp (via whatsapp-service) à l'étudiant ou aux
 * acteurs internes lorsqu'un document est généré ou mis à disposition.
 *
 * Règles :
 *  - Jamais bloquant : tout échec est absorbé silencieusement
 *  - Aucun emoji — mise en forme WhatsApp (*gras*, _italique_)
 *  - Le numéro est normalisé automatiquement (format béninois +229...)
 *
 * REFONTE (v2) — PÉRIMÈTRE RÉDUIT AU RÉELLEMENT UTILISÉ :
 *   Un audit du code appelant a montré que seule sendQuittanceNotification()
 *   est effectivement invoquée (depuis QuittanceController). Les méthodes
 *   sendSoumission(), sendDocumentGenere(), sendBulletinGenere() et
 *   sendBatchNotification() n'avaient aucun appelant dans le code fourni et
 *   ont été retirées de ce fichier pour ne pas maintenir du code mort.
 *   Si l'un de ces déclencheurs doit être branché à l'avenir (génération
 *   d'attestation, de bulletin, batch staff...), il faudra le réintroduire
 *   avec son véritable appelant.
 *
 *   Le message de sendQuittanceNotification() a été enrichi avec le nom de
 *   l'étudiant, disponible dans QuittanceController ($request->nomEtudiant),
 *   et ne comporte plus de séparateur visuel.
 */
class WhatsAppNotificationService
{
    private const ETAB = 'CAP-EPAC';

    public function __construct(
        private WhatsAppBridgeClient $bridge,
    ) {}

    // ═════════════════════════════════════════════════════════════════════════
    // NOTIFICATIONS ÉTUDIANT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Notification de quittance de paiement générée.
     *
     * @param  string  $phone           Numéro WhatsApp
     * @param  string  $quittanceNumber Numéro de quittance
     * @param  string  $reference       Référence de la demande
     * @param  string  $nomEtudiant     Nom de l'étudiant (affiché dans le message)
     */
    public function sendQuittanceNotification(
        string $phone,
        string $quittanceNumber,
        string $reference,
        string $nomEtudiant,
    ): bool {
        if (empty($phone)) {
            return false;
        }

        $message = implode("\n", [
            '*' . self::ETAB . ' — Quittance générée*',
            "Bonjour {$nomEtudiant},",
            "Votre quittance n°*{$quittanceNumber}* a été générée avec succès pour la demande *{$reference}*.",
            "Elle vous a également été transmise par e-mail sur l'adresse renseignée.",
        ]);

        return $this->bridge->send($phone, $message, "attestation-quittance:{$quittanceNumber}");
    }
}

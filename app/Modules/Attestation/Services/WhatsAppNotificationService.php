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
 * Déclencheurs attendus (à appeler depuis AttestationController) :
 *
 *   sendDocumentGenere()      → document généré & prêt à télécharger   (étudiant)
 *   sendBulletinGenere()      → bulletin généré                         (étudiant)
 *   sendBatchNotification()   → génération de masse terminée             (staff)
 */
class WhatsAppNotificationService
{
    private const DIVIDER   = '――――――――――――――――――';
    private const ETAB      = 'CAP-EPAC';

    public function __construct(
        private WhatsAppBridgeClient $bridge,
    ) {}

    // ═════════════════════════════════════════════════════════════════════════
    // NOTIFICATIONS ÉTUDIANT
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Soumission d'une demande d'attestation.
     *
     * @param  string  $phone      Numéro WhatsApp de l'étudiant
     * @param  string  $reference  Référence de la demande
     * @param  string  $typeDoc    Libellé du document (ex: "Attestation de Passage")
     */
    public function sendSoumission(string $phone, string $reference, string $typeDoc): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = implode("\n", [
            "*Demande Reçue — " . self::ETAB . "*",
            self::DIVIDER,
            "Votre demande de *{$typeDoc}* a bien été enregistrée.",
            "",
            "Référence : *{$reference}*",
            "",
            "Vous recevrez une notification dès que votre document sera disponible.",
        ]);

        return $this->bridge->send($phone, $message, "attestation-soumission:{$reference}");
    }

    /**
     * Notification de quittance de paiement générée.
     *
     * @param  string  $phone           Numéro WhatsApp
     * @param  string  $quittanceNumber Numéro de quittance
     * @param  string  $reference       Référence de la demande
     */
    public function sendQuittanceNotification(string $phone, string $quittanceNumber, string $reference): bool
    {
        if (empty($phone)) {
            return false;
        }

        $message = implode("\n", [
            "*Quittance Générée — " . self::ETAB . "*",
            self::DIVIDER,
            "Votre quittance de paiement n°*{$quittanceNumber}* a été générée avec succès pour la demande *{$reference}*.",
            "",
            "Elle vous a été envoyée par email.",
        ]);

        return $this->bridge->send($phone, $message, "attestation-quittance:{$quittanceNumber}");
    }

    /**
     * Notifie l'étudiant qu'une attestation / un certificat est disponible.
     *
     * @param  string       $phone       Numéro WhatsApp de l'étudiant
     * @param  string       $nomEtudiant Prénom + Nom
     * @param  string       $typeDoc     Ex: "Attestation de succès", "Certificat de scolarité"
     * @param  string       $matricule   Numéro étudiant
     * @param  string|null  $downloadUrl URL de téléchargement si disponible
     */
    public function sendDocumentGenere(
        string  $phone,
        string  $nomEtudiant,
        string  $typeDoc,
        string  $matricule = '',
        ?string $downloadUrl = null,
    ): bool {
        if (empty($phone)) {
            return false;
        }

        $message = $this->templateDocumentGenere($nomEtudiant, $typeDoc, $matricule, $downloadUrl);
        return $this->bridge->send($phone, $message, "attestation-doc:{$matricule}");
    }

    /**
     * Notifie l'étudiant qu'un bulletin est disponible.
     *
     * @param  string  $phone          Numéro WhatsApp de l'étudiant
     * @param  string  $nomEtudiant    Prénom + Nom
     * @param  string  $matricule      Numéro étudiant
     * @param  string  $anneeAcad      Ex: "2023-2024"
     * @param  string  $decision       Ex: "Admis", "Redouble", "Exclu"
     * @param  float   $moyenne        Moyenne générale (sur 100)
     */
    public function sendBulletinGenere(
        string $phone,
        string $nomEtudiant,
        string $matricule,
        string $anneeAcad,
        string $decision,
        float  $moyenne,
    ): bool {
        if (empty($phone)) {
            return false;
        }

        $message = $this->templateBulletinGenere($nomEtudiant, $matricule, $anneeAcad, $decision, $moyenne);
        return $this->bridge->send($phone, $message, "attestation-bulletin:{$matricule}:{$anneeAcad}");
    }

    // ═════════════════════════════════════════════════════════════════════════
    // NOTIFICATIONS STAFF (génération de masse)
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Notifie un acteur interne qu'une génération de masse est terminée.
     *
     * @param  string  $phone           Numéro WhatsApp du destinataire
     * @param  string  $destinataireNom Nom de l'acteur
     * @param  string  $typeDoc         Type de document généré
     * @param  int     $nbDocuments     Nombre de documents produits
     * @param  string  $anneeAcad       Année académique concernée
     */
    public function sendBatchNotification(
        string $phone,
        string $destinataireNom,
        string $typeDoc,
        int    $nbDocuments,
        string $anneeAcad = '',
    ): bool {
        if (empty($phone)) {
            return false;
        }

        $message = $this->templateBatchNotification($destinataireNom, $typeDoc, $nbDocuments, $anneeAcad);
        return $this->bridge->send($phone, $message, "attestation-batch:{$typeDoc}");
    }

    // ═════════════════════════════════════════════════════════════════════════
    // TEMPLATES
    // ═════════════════════════════════════════════════════════════════════════

    private function templateDocumentGenere(
        string  $nomEtudiant,
        string  $typeDoc,
        string  $matricule,
        ?string $downloadUrl,
    ): string {
        $lines = [
            "*{$typeDoc} disponible — " . self::ETAB . "*",
            self::DIVIDER,
            "Bonjour *{$nomEtudiant}*,",
            "",
            "Votre *{$typeDoc}* est prêt." . ($matricule ? " (Matricule : {$matricule})" : ''),
            "",
        ];

        if ($downloadUrl) {
            $lines[] = "Télécharger votre document : {$downloadUrl}";
            $lines[] = "";
        } else {
            $lines[] = "Vous pouvez le récupérer auprès du secrétariat durant les heures d'ouverture.";
            $lines[] = "";
        }

        $lines[] = "Merci de votre confiance.";

        return implode("\n", $lines);
    }

    private function templateBulletinGenere(
        string $nomEtudiant,
        string $matricule,
        string $anneeAcad,
        string $decision,
        float  $moyenne,
    ): string {
        return implode("\n", [
            "*Bulletin de Notes disponible — " . self::ETAB . "*",
            self::DIVIDER,
            "Bonjour *{$nomEtudiant}*,",
            "",
            "Votre bulletin de notes pour l'année académique *{$anneeAcad}* est disponible.",
            "",
            "Matricule : *{$matricule}*",
            "Moyenne générale : *{$moyenne}/100*",
            "Décision : *{$decision}*",
            "",
            "Vous pouvez le récupérer auprès du secrétariat.",
        ]);
    }

    private function templateBatchNotification(
        string $destinataireNom,
        string $typeDoc,
        int    $nbDocuments,
        string $anneeAcad,
    ): string {
        $label = $nbDocuments <= 1 ? 'document généré' : 'documents générés';
        $lines = [
            "*Génération Terminée — " . self::ETAB . "*",
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            "",
            "La génération de masse des *{$typeDoc}* est terminée.",
            "",
            "Nombre de {$label} : *{$nbDocuments}*",
        ];

        if ($anneeAcad) {
            $lines[] = "Année académique : *{$anneeAcad}*";
        }

        $lines[] = "";
        $lines[] = "Les documents sont disponibles dans le portail de gestion.";

        return implode("\n", $lines);
    }
}

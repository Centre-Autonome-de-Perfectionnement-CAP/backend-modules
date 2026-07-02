<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Core\Services\WhatsAppBridgeClient;

/**
 * Service WhatsApp du module Demandes.
 *
 * Adaptateur de haut niveau :
 *   - Expose les templates métier (soumission, rejet, prêt…)
 *   - Délègue l'envoi réel au WhatsAppBridgeClient (micro-service Node.js / Baileys)
 *
 * AJOUT : isConnected() — exposé pour SendNotificationJob.
 *         Délègue à WhatsAppBridgeClient::isConnected() qui existe déjà.
 *         Le Job l'appelle avant d'envoyer pour éviter de brûler une tentative
 *         si le bridge est temporairement déconnecté.
 *
 * Aucune autre modification par rapport à l'original.
 */
class WhatsAppService
{
    private const DIVIDER = '――――――――――――――――――';

    public function __construct(
        private WhatsAppBridgeClient $bridge,
    ) {}

    // ── Envoi principal ───────────────────────────────────────────────────────

    public function send(string $phone, string $message, string $context = ''): bool
    {
        return $this->bridge->send($phone, $message, $context);
    }

    public function normalizePhone(string $phone): ?string
    {
        return $this->bridge->normalizePhone($phone);
    }

    // ── AJOUT : statut bridge ─────────────────────────────────────────────────

    /**
     * Vérifie si le bridge WhatsApp est connecté.
     * Utilisé par SendNotificationJob::handleWhatsApp() avant chaque envoi.
     * WhatsAppBridgeClient::isConnected() met le résultat en cache 10s
     * pour ne pas saturer le bridge de pings.
     */
    public function isConnected(): bool
    {
        return $this->bridge->isConnected();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // TEMPLATES (INCHANGÉS)
    // ══════════════════════════════════════════════════════════════════════════

    public function templateSoumission(string $reference, string $typeLabel, string $email): string
    {
        return implode("\n", [
            '*Demande Reçue — CAP-EPAC*',
            self::DIVIDER,
            "Votre demande de *{$typeLabel}* a bien été enregistrée.",
            '',
            "Référence : *{$reference}*",
            '',
            "Vous serez notifié(e) à chaque étape du traitement.",
        ]);
    }

    public function templateComplementEtudiant(string $reference, array $piecesList): string
    {
        $nb    = count($piecesList);
        $label = $nb > 1 ? 'pièces complémentaires reçues' : 'pièce complémentaire reçue';
        $lines = [
            '*Complément Reçu — CAP-EPAC*',
            self::DIVIDER,
            "Pour votre demande (Réf : *{$reference}*), nous avons bien reçu {$nb} {$label} :",
            '',
        ];
        foreach ($piecesList as $piece) {
            $lines[] = "• {$piece}";
        }
        $lines[] = '';
        $lines[] = 'Elles ont été transmises au secrétariat pour vérification.';
        return implode("\n", $lines);
    }

    public function templatePret(string $reference, string $typeLabel): string
    {
        return implode("\n", [
            '*Document Prêt — CAP-EPAC*',
            self::DIVIDER,
            "Votre *{$typeLabel}* (Réf : *{$reference}*) est prêt.",
            '',
            'Vous pouvez venir le récupérer au secrétariat.',
        ]);
    }

    public function templateRejete(string $reference, string $typeLabel, string $motif): string
    {
        return implode("\n", [
            '*Demande Rejetée — CAP-EPAC*',
            self::DIVIDER,
            "Votre demande de *{$typeLabel}* (Réf : *{$reference}*) a été rejetée.",
            '',
            "*Motif :* {$motif}",
            '',
            'Rapprochez-vous du secrétariat pour plus d\'informations.',
        ]);
    }

    public function templateSousReserve(string $reference, string $typeLabel, string $motif): string
    {
        return implode("\n", [
            '*Dossier Sous Réserve — CAP-EPAC*',
            self::DIVIDER,
            "Votre demande de *{$typeLabel}* (Réf : *{$reference}*) nécessite votre attention.",
            '',
            "*Motif :* {$motif}",
            '',
            'Veuillez soumettre un complément de dossier via le portail.',
        ]);
    }

    public function templateRemis(string $reference, string $typeLabel): string
    {
        return implode("\n", [
            '*Document Retiré — CAP-EPAC*',
            self::DIVIDER,
            "Votre *{$typeLabel}* (Réf : *{$reference}*) vous a été remis.",
            '',
            'Merci et bonne continuation !',
        ]);
    }

    public function templateNouvelleDemandeSecretaire(
        string $destinataireNom,
        string $reference,
        string $typeDocument,
        string $nomEtudiant,
        string $matricule,
    ): string {
        return implode("\n", [
            '*Nouvelle Demande — CAP-EPAC*',
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            '',
            "Une nouvelle demande de *{$typeDocument}* a été soumise.",
            '',
            "Étudiant : *{$nomEtudiant}*" . ($matricule ? " ({$matricule})" : ''),
            "Référence : *{$reference}*",
            '',
            'Veuillez initier le traitement du dossier.',
        ]);
    }

    public function templateComplementSecretaire(
        string $destinataireNom,
        string $reference,
        string $nomEtudiant,
        int    $nbPieces,
    ): string {
        $label = $nbPieces > 1 ? 'pièces complémentaires' : 'pièce complémentaire';
        return implode("\n", [
            '*Complément de Dossier — CAP-EPAC*',
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            '',
            "L'étudiant(e) *{$nomEtudiant}* (Réf : *{$reference}*) vient de déposer {$nbPieces} {$label}.",
            '',
            'Veuillez vérifier les nouveaux documents dans le portail.',
        ]);
    }

    public function templateActeurDossier(
        string  $destinataireNom,
        string  $destinataireRole,
        string  $expediteurNom,
        string  $expediteurRole,
        string  $reference,
        string  $typeDocument,
        string  $etudiantNom,
        string  $matricule,
        ?string $commentaire,
    ): string {
        $lines = [
            '*Nouveau Dossier à Traiter — CAP-EPAC*',
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            '',
            "*[{$expediteurRole}] {$expediteurNom}* vous a transmis le dossier suivant :",
            '',
            "Étudiant : *{$etudiantNom}*" . ($matricule ? " ({$matricule})" : ''),
            "Document : *{$typeDocument}*",
            "Référence : *{$reference}*",
            '',
        ];
        if ($commentaire) {
            $lines[] = "*Note :* {$commentaire}";
            $lines[] = '';
        }
        $lines[] = 'Veuillez vous connecter pour traiter ce dossier.';
        return implode("\n", $lines);
    }

    public function templateCorrectionCircuit(
        string  $destinataireNom,
        string  $expediteurNom,
        string  $expediteurRole,
        string  $reference,
        string  $typeDocument,
        string  $etudiantNom,
        string  $matricule,
        ?string $commentaire,
    ): string {
        $lines = [
            '*Dossier Renvoyé pour Correction — CAP-EPAC*',
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            '',
            "Le dossier de *{$etudiantNom}* (Réf : *{$reference}*) vous a été renvoyé par *[{$expediteurRole}] {$expediteurNom}*.",
            '',
        ];
        if ($commentaire) {
            $lines[] = "*Motif :* {$commentaire}";
            $lines[] = '';
        }
        $lines[] = 'Veuillez corriger la demande et la renvoyer.';
        return implode("\n", $lines);
    }

    public function templateDossierDirection(
        string $destinataireNom,
        string $nomEtudiant,
        string $reference,
    ): string {
        return implode("\n", [
            '*Dossier en Direction — CAP-EPAC*',
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            '',
            "Le dossier de *{$nomEtudiant}* (Réf : *{$reference}*) est en cours de signature (Direction).",
            '',
            'Veuillez préparer et acheminer les documents physiques correspondants.',
        ]);
    }

    public function templateDirecteurSigne(
        string $destinataireNom,
        string $nomEtudiant,
        string $reference,
        string $typeDocument,
        string $matricule,
    ): string {
        return implode("\n", [
            '*Signature Directeur — Action Requise — CAP-EPAC*',
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            '',
            'Le *Directeur* vient de signer le dossier suivant :',
            '',
            "Étudiant : *{$nomEtudiant}*" . ($matricule ? " ({$matricule})" : ''),
            "Document : *{$typeDocument}*",
            "Référence : *{$reference}*",
            '',
            'Veuillez préparer le document et le marquer comme *prêt à retirer* dans le portail.',
        ]);
    }
}

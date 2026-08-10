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
 * REFONTE (v3) :
 *   - Suppression des séparateurs visuels (――――) dans tous les templates.
 *   - Ajout d'un accueil personnalisé ("Bonjour {nomEtudiant},") sur les
 *     messages destinés à l'étudiant.
 *   - Aucun émoji. Mise en forme WhatsApp conservée (*gras*, _italique_).
 *   - Liens de suivi/complément externalisés dans des constantes en tête
 *     de fichier (URL_SUIVI / URL_COMPLEMENT) pour pouvoir changer le
 *     domaine après déploiement sans toucher aux templates.
 *   - templateActeurDossier() / templateCorrectionCircuit() : le nom de
 *     l'expéditeur n'est plus affiché, seul son rôle l'est (libellé
 *     WorkflowConstants::ROLE_LABELS, déjà résolu par l'appelant).
 *
 * AJOUT (v2) : isConnected() — exposé pour SendNotificationJob.
 *         Délègue à WhatsAppBridgeClient::isConnected() qui existe déjà.
 *         Le Job l'appelle avant d'envoyer pour éviter de brûler une tentative
 *         si le bridge est temporairement déconnecté.
 */
class WhatsAppService
{
    // ── Liens (à adapter au vrai domaine de production) ─────────────────────
    // Un seul endroit à modifier après déploiement.
    private const URL_SUIVI      = 'http://cap.the-haute-societyy.com/student-services?type=suivi';
    private const URL_COMPLEMENT = 'http://cap.the-haute-societyy.com/student-services?type=complement-dossier';

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
    // TEMPLATES — ÉTUDIANT
    // ══════════════════════════════════════════════════════════════════════════

    public function templateSoumission(string $reference, string $typeLabel, string $nomEtudiant): string
    {
        return implode("\n", [
            '*CAP-EPAC — Demande enregistrée*',
            "Bonjour {$nomEtudiant},",
            "Votre demande de *{$typeLabel}* a bien été enregistrée sous la référence *{$reference}*.",
            'Veuillez conserver cette référence : elle vous sera utile pour tout suivi ou réclamation.',
            "Vous serez notifié(e) en temps réel sur l'état de votre demande.",
            '',
            'Suivi de votre dossier sur : ' . self::URL_SUIVI . "?ref={$reference}",
        ]);
    }

    public function templateComplementEtudiant(string $reference, array $piecesList, string $nomEtudiant): string
    {
        $nb    = count($piecesList);
        $label = $nb > 1 ? 'pièces complémentaires' : 'pièce complémentaire';
        $lines = [
            '*CAP-EPAC — Complément reçu*',
            "Bonjour {$nomEtudiant},",
            "Nous avons bien reçu {$nb} {$label} pour votre dossier (Réf : *{$reference}*) :",
        ];
        foreach ($piecesList as $piece) {
            $lines[] = "• {$piece}";
        }
        $lines[] = 'Elles ont été transmises au secrétariat pour vérification.';
        $lines[] = '';
        $lines[] = 'Suivi de votre dossier sur : ' . self::URL_SUIVI . "?ref={$reference}";
        return implode("\n", $lines);
    }

    public function templatePret(string $reference, string $typeLabel, string $nomEtudiant): string
    {
        return implode("\n", [
            '*CAP-EPAC — Document prêt*',
            "Bonjour {$nomEtudiant},",
            "Votre *{$typeLabel}* (Réf : *{$reference}*) est prêt.",
            "Vous pouvez venir le récupérer au secrétariat durant les heures d'ouverture.",
        ]);
    }

    public function templateRejete(string $reference, string $typeLabel, string $motif, string $nomEtudiant): string
    {
        return implode("\n", [
            '*CAP-EPAC — Demande rejetée*',
            "Bonjour {$nomEtudiant},",
            "Votre demande de *{$typeLabel}* (Réf : *{$reference}*) n'a pas pu aboutir.",
            "Motif : {$motif}",
            'Rapprochez-vous du secrétariat pour plus d\'informations.',
        ]);
    }

    public function templateSousReserve(string $reference, string $typeLabel, string $motif, string $nomEtudiant): string
    {
        return implode("\n", [
            '*CAP-EPAC — Action requise*',
            "Bonjour {$nomEtudiant},",
            "Votre demande de *{$typeLabel}* (Réf : *{$reference}*) nécessite un complément.",
            "Motif : {$motif}",
            'Merci de soumettre les pièces manquantes via ce lien : ' . self::URL_COMPLEMENT . "?ref={$reference}",
        ]);
    }

    public function templateRemis(string $reference, string $typeLabel, string $nomEtudiant): string
    {
        return implode("\n", [
            '*CAP-EPAC — Document remis*',
            "Bonjour {$nomEtudiant},",
            "Votre *{$typeLabel}* (Réf : *{$reference}*) vous a bien été remis.",
            'Merci de votre confiance.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // TEMPLATES — PERSONNEL (STAFF)
    // ══════════════════════════════════════════════════════════════════════════

    public function templateNouvelleDemandeSecretaire(
        string $destinataireNom,
        string $reference,
        string $typeDocument,
        string $nomEtudiant,
        string $matricule,
    ): string {
        return implode("\n", [
            '*CAP-EPAC — Nouvelle demande*',
            "Bonjour {$destinataireNom},",
            "L'étudiant(e) *{$nomEtudiant}*" . ($matricule ? " ({$matricule})" : '') . " a soumis une demande de *{$typeDocument}*.",
            "Référence : *{$reference}*",
            'Merci d\'initier le traitement depuis votre tableau de bord.',
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
            '*CAP-EPAC — Complément déposé*',
            "Bonjour {$destinataireNom},",
            "*{$nomEtudiant}* a déposé {$nbPieces} {$label} pour le dossier *{$reference}*.",
            'Merci de vérifier ces nouveaux éléments.',
        ]);
    }

    /**
     * Transmission d'un dossier à un acteur du circuit.
     * Le rôle affiché ({$expediteurRole}) est déjà le libellé résolu via
     * WorkflowConstants::ROLE_LABELS par l'appelant (NotificationService) —
     * aucun nom d'expéditeur n'apparaît dans le message.
     */
    public function templateActeurDossier(
        string  $destinataireNom,
        string  $expediteurRole,
        string  $reference,
        string  $typeDocument,
        string  $etudiantNom,
        string  $matricule,
        ?string $commentaire,
    ): string {
        $etudiantAffiche = $etudiantNom . ($matricule ? " ({$matricule})" : '');
        $lines = [
            '*CAP-EPAC — Dossier à traiter*',
            "Bonjour {$destinataireNom},",
            "*[{$expediteurRole}]* vous a transmis le dossier *{$reference}* (*{$typeDocument}* — {$etudiantAffiche}).",
        ];
        if ($commentaire) {
            $lines[] = "Note : {$commentaire}";
        }
        $lines[] = 'Merci de vous connecter pour le traiter.';
        return implode("\n", $lines);
    }

    /**
     * Retour de dossier pour correction.
     * Même règle que templateActeurDossier() : rôle seul, pas de nom.
     */
    public function templateCorrectionCircuit(
        string  $destinataireNom,
        string  $expediteurRole,
        string  $reference,
        string  $typeDocument,
        string  $etudiantNom,
        string  $matricule,
        ?string $commentaire,
    ): string {
        $etudiantAffiche = $etudiantNom . ($matricule ? " ({$matricule})" : '');
        $lines = [
            '*CAP-EPAC — Retour pour correction*',
            "Bonjour {$destinataireNom},",
            "Le dossier *{$reference}* ({$etudiantAffiche}) vous a été renvoyé par *[{$expediteurRole}]*.",
        ];
        if ($commentaire) {
            $lines[] = "Motif : {$commentaire}";
        }
        $lines[] = 'Merci de corriger et renvoyer la demande.';
        return implode("\n", $lines);
    }

    public function templateDossierDirection(
        string $destinataireNom,
        string $nomEtudiant,
        string $reference,
    ): string {
        return implode("\n", [
            '*CAP-EPAC — Dossier en Direction*',
            "Bonjour {$destinataireNom},",
            "Le dossier de *{$nomEtudiant}* (Réf : *{$reference}*) est en cours de signature à la Direction.",
            'Merci de préparer et acheminer le document physique correspondant.',
        ]);
    }

    public function templateDirecteurSigne(
        string $destinataireNom,
        string $nomEtudiant,
        string $reference,
        string $typeDocument,
        string $matricule,
    ): string {
        $etudiantAffiche = $nomEtudiant . ($matricule ? " ({$matricule})" : '');
        return implode("\n", [
            '*CAP-EPAC — Signature validée*',
            "Bonjour {$destinataireNom},",
            "Le *Directeur* a signé le dossier *{$reference}* (*{$typeDocument}* — {$etudiantAffiche}).",
            'Merci de préparer le document final et de le marquer "prêt" dans le système.',
        ]);
    }
}

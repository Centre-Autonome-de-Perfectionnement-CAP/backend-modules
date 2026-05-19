<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Core\Services\WhatsAppBridgeClient;

/**
 * Service WhatsApp du module Demandes.
 *
 * Ce service est un adaptateur de haut niveau :
 *   - Il expose les templates métier (soumission, rejet, prêt…)
 *   - Il délègue l'envoi réel au WhatsAppBridgeClient (micro-service Node.js / Baileys)
 *
 * Migration : ce service remplaçait l'ancienne intégration Twilio.
 * Toute la normalisation de numéros est désormais gérée par WhatsAppBridgeClient.
 *
 * Règles :
 *  - Jamais bloquant : tout échec est loggué silencieusement par le bridge
 *  - Jamais d'exception propagée vers le workflow
 *  - Aucun emoji — mise en forme WhatsApp (*gras*, _italique_)
 */
class WhatsAppService
{
    private const DIVIDER = '――――――――――――――――――';

    public function __construct(
        private WhatsAppBridgeClient $bridge,
    ) {}

    // ── Envoi principal ───────────────────────────────────────────────────────

    /**
     * Envoie un message WhatsApp.
     *
     * @param  string  $phone    Numéro brut (tout format béninois)
     * @param  string  $message  Corps du message
     * @param  string  $context  Identifiant pour les logs (ex: "soumission:REF-001")
     */
    public function send(string $phone, string $message, string $context = ''): bool
    {
        return $this->bridge->send($phone, $message, $context);
    }

    /**
     * Normalise un numéro de téléphone vers le format international E.164.
     * Délègue au bridge qui centralise cette logique.
     */
    public function normalizePhone(string $phone): ?string
    {
        return $this->bridge->normalizePhone($phone);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // TEMPLATES — ÉTUDIANT
    // ═════════════════════════════════════════════════════════════════════════

    public function templateSoumission(string $reference, string $typeLabel, string $email): string
    {
        $suiviUrl = config('app.url') . '/app-cap/student-services?ref=' . $reference;
        return implode("\n", [
            "*Demande Reçue — CAP-EPAC*",
            self::DIVIDER,
            "Votre demande de *{$typeLabel}* a bien été enregistrée.",
            "",
            "Référence : *{$reference}*",
            "",
            "Votre dossier est en cours d'examen. Vous serez notifié(e) à chaque étape.",
            "",
            "Suivez l'avancement : {$suiviUrl}",
        ]);
    }

    public function templateComplementEtudiant(string $reference, array $piecesList): string
    {
        $nb       = count($piecesList);
        $label    = $nb <= 1 ? 'pièce complémentaire reçue' : 'pièces complémentaires reçues';
        $suiviUrl = config('app.url') . '/app-cap/student-services?ref=' . $reference;

        $lines = [
            "*Complément Reçu — CAP-EPAC*",
            self::DIVIDER,
            "Pour votre demande (Réf: *{$reference}*), nous avons bien reçu {$nb} {$label} :",
            "",
        ];

        foreach ($piecesList as $piece) {
            $lines[] = "- {$piece}";
        }

        $lines[] = "";
        $lines[] = "Elles ont été transmises au secrétariat pour vérification.";
        $lines[] = "";
        $lines[] = "Suivez l'avancement : {$suiviUrl}";

        return implode("\n", $lines);
    }

    public function templatePret(string $reference, string $typeLabel): string
    {
        return implode("\n", [
            "*Document Prêt — CAP-EPAC*",
            self::DIVIDER,
            "Votre demande de *{$typeLabel}* (Réf: *{$reference}*) a été traitée avec succès.",
            "",
            "Vous pouvez venir récupérer votre document au secrétariat durant les heures d'ouverture.",
        ]);
    }

    public function templateRejete(string $reference, string $typeLabel, string $motif): string
    {
        return implode("\n", [
            "*Demande Rejetée — CAP-EPAC*",
            self::DIVIDER,
            "Votre demande de *{$typeLabel}* (Réf: *{$reference}*) n'a pas pu aboutir.",
            "",
            "*Motif :* {$motif}",
            "",
            "Veuillez corriger ces éléments ou vous rapprocher du secrétariat.",
        ]);
    }

    public function templateSousReserve(string $reference, string $typeLabel, string $motif): string
    {
        $suiviUrl = config('app.url') . '/app-cap/student-services?ref=' . $reference;
        return implode("\n", [
            "*Dossier Sous Réserve — CAP-EPAC*",
            self::DIVIDER,
            "Votre demande de *{$typeLabel}* (Réf: *{$reference}*) est en cours de traitement mais nécessite votre attention.",
            "",
            "*Motif :* {$motif}",
            "",
            "Veuillez régulariser en soumettant un complément de dossier en ligne.",
            "",
            "Accéder au portail : {$suiviUrl}",
        ]);
    }

    public function templateRemis(string $reference, string $typeLabel): string
    {
        return implode("\n", [
            "*Document Retiré — CAP-EPAC*",
            self::DIVIDER,
            "Votre document *{$typeLabel}* (Réf: *{$reference}*) vous a été remis avec succès.",
            "",
            "Merci de votre confiance et bonne continuation !",
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // TEMPLATES — ACTEURS INTERNES
    // ═════════════════════════════════════════════════════════════════════════

    public function templateNouvelleDemandeSecretaire(
        string $destinataireNom,
        string $reference,
        string $typeDocument,
        string $nomEtudiant,
        string $matricule,
    ): string {
        return implode("\n", [
            "*Nouvelle Demande — CAP-EPAC*",
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            "",
            "Une nouvelle demande de *{$typeDocument}* a été soumise.",
            "",
            "Étudiant : *{$nomEtudiant}*" . ($matricule ? " ({$matricule})" : ''),
            "Référence : *{$reference}*",
            "",
            "Veuillez vérifier et initier le traitement du dossier.",
        ]);
    }

    public function templateComplementSecretaire(
        string $destinataireNom,
        string $reference,
        string $nomEtudiant,
        int    $nbPieces,
    ): string {
        $label = $nbPieces <= 1 ? 'pièce complémentaire' : 'pièces complémentaires';
        return implode("\n", [
            "*Complément de Dossier — CAP-EPAC*",
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            "",
            "L'étudiant(e) *{$nomEtudiant}* (Réf: *{$reference}*) vient de déposer {$nbPieces} {$label}.",
            "",
            "Veuillez vérifier les nouveaux documents dans le portail.",
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
        string  $matricule = '',
        ?string $commentaire = null,
    ): string {
        $lines = [
            "*Nouveau Dossier à Traiter — CAP-EPAC*",
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            "",
            "*[{$expediteurRole}] {$expediteurNom}* vient de vous transmettre le dossier suivant :",
            "",
            "Étudiant : *{$etudiantNom}*" . ($matricule ? " ({$matricule})" : ''),
            "Document : *{$typeDocument}*",
            "Référence : *{$reference}*",
            "",
        ];

        if ($commentaire) {
            $lines[] = "*Note :* {$commentaire}";
            $lines[] = "";
        }

        $lines[] = "Veuillez vous connecter pour traiter ce dossier.";

        return implode("\n", $lines);
    }

    public function templateCorrectionCircuit(
        string  $destinataireNom,
        string  $expediteurNom,
        string  $expediteurRole,
        string  $reference,
        string  $typeDocument,
        string  $etudiantNom,
        string  $matricule = '',
        ?string $commentaire = null,
    ): string {
        $lines = [
            "*Dossier Renvoyé pour Correction — CAP-EPAC*",
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            "",
            "Le dossier de *{$etudiantNom}* (Réf: *{$reference}*) vous a été renvoyé par *[{$expediteurRole}] {$expediteurNom}*.",
            "",
        ];

        if ($commentaire) {
            $lines[] = "*Motif :* {$commentaire}";
            $lines[] = "";
        }

        $lines[] = "Veuillez vous connecter pour corriger la demande.";

        return implode("\n", $lines);
    }

    public function templateDossierDirection(
        string $destinataireNom,
        string $nomEtudiant,
        string $reference,
    ): string {
        return implode("\n", [
            "*Dossier en Direction — CAP-EPAC*",
            self::DIVIDER,
            "Bonjour *{$destinataireNom}*,",
            "",
            "Le dossier de *{$nomEtudiant}* (Réf: *{$reference}*) vient d'être transmis à la Direction.",
            "",
            "Merci de préparer et d'acheminer le dossier physique correspondant.",
        ]);
    }
}

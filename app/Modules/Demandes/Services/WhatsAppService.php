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
 * AJOUT (v2) : isConnected() - exposé pour SendNotificationJob.
 *         Délègue à WhatsAppBridgeClient::isConnected() qui existe déjà.
 *         Le Job l'appelle avant d'envoyer pour éviter de brûler une tentative
 *         si le bridge est temporairement déconnecté.
 */
class WhatsAppService
{
    // ── Liens (adaptés dynamiquement à l'environnement) ─────────────────────
    // CORRECTIF : les URLs étaient codées en dur sur un domaine de dev
    // ("cap.the-haute-societyy.com") qui ne correspond ni à la prod ni au
    // local. On utilise désormais FRONTEND_URL (config('app.frontend_url')),
    // déjà défini dans config/app.php avec fallback localhost:5173 - un seul
    // endroit à changer (le .env) pour prod/local/staging.
    private function urlSuivi(): string
    {
        // CORRECTIF : le site vitrine attend type=suivi-demandes (type=suivi
        // n'existe pas → la page affichait la liste des services, sans suivi).
        return rtrim(config('app.frontend_url'), '/') . '/student-services?type=suivi-demandes';
    }

    private function urlComplement(): string
    {
        return rtrim(config('app.frontend_url'), '/') . '/student-services?type=complement-dossier';
    }

    public function __construct(
        private WhatsAppBridgeClient $bridge,
    ) {}

    // ── Envoi principal ───────────────────────────────────────────────────────

    public function send(string $phone, string $message, string $context = ''): bool
    {
        return $this->bridge->send($phone, $message, $context);
    }

    /**
     * Envoie un fichier WhatsApp (pièce jointe) - passthrough vers le bridge.
     * Utilisé par ContactDemandeurService (message libre secrétaire → demandeur).
     */
    public function sendFile(
        string $phone,
        string $disk,
        string $path,
        string $fileName,
        string $caption = '',
        string $context = '',
    ): bool {
        return $this->bridge->sendFile($phone, $disk, $path, $fileName, $caption, $context);
    }

    /**
     * Envoi qualifié (succès / échec transitoire / échec définitif) -
     * utilisé par SendNotificationJob pour décider d'un retry.
     *
     * @return array{ok: bool, retryable: bool, http: int, error: ?string}
     */
    public function sendDetailed(string $phone, string $message, string $context = ''): array
    {
        return $this->bridge->sendDetailed($phone, $message, $context);
    }

    /**
     * @return array{ok: bool, retryable: bool, http: int, error: ?string}
     */
    public function sendFileDetailed(
        string $phone,
        string $disk,
        string $path,
        string $fileName,
        string $caption = '',
        string $context = '',
    ): array {
        return $this->bridge->sendFileDetailed($phone, $disk, $path, $fileName, $caption, $context);
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
    // MISE EN FORME COMMUNE
    // ══════════════════════════════════════════════════════════════════════════
    //
    // Tous les messages suivent la même structure, aérée et lisible sur
    // téléphone (aucun émoji) :
    //
    //   *CAP-EPAC*
    //   _Titre du message_
    //
    //   Bonjour Nom,
    //
    //   Paragraphe principal.
    //
    //   *Référence :* ATT-XXXX
    //   *Document :* Attestation …
    //
    //   Consigne ou lien.

    /**
     * @param string[] $blocks Blocs de texte, séparés par une ligne vide.
     *                         Les blocs vides sont ignorés.
     */
    private function compose(string $title, array $blocks): string
    {
        $blocks = array_values(array_filter(
            array_map(fn ($b) => trim((string) $b), $blocks),
            fn ($b) => $b !== ''
        ));

        return "*CAP-EPAC*\n_{$title}_\n\n" . implode("\n\n", $blocks);
    }

    /**
     * Lignes « *Libellé :* valeur » (les valeurs vides sont omises).
     *
     * @param array<string, ?string> $fields
     */
    private function fields(array $fields): string
    {
        $lines = [];
        foreach ($fields as $label => $value) {
            if ($value === null || trim($value) === '') continue;
            $lines[] = "*{$label} :* {$value}";
        }
        return implode("\n", $lines);
    }

    private function student(string $nom, string $matricule): string
    {
        return $nom . ($matricule ? " ({$matricule})" : '');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // TEMPLATES - ÉTUDIANT
    // ══════════════════════════════════════════════════════════════════════════

    public function templateSoumission(string $reference, string $typeLabel, string $nomEtudiant): string
    {
        return $this->compose('Demande enregistrée', [
            "Bonjour {$nomEtudiant},",
            "Votre demande de *{$typeLabel}* a bien été enregistrée.",
            $this->fields(['Référence' => $reference]),
            "Conservez cette référence : elle vous sera demandée pour tout suivi.\nVous serez informé(e) à chaque étape du traitement.",
            "_Suivre votre dossier :_\n" . $this->urlSuivi() . "&ref={$reference}",
        ]);
    }

    public function templateComplementEtudiant(string $reference, array $piecesList, string $nomEtudiant): string
    {
        $nb    = count($piecesList);
        $label = $nb > 1 ? 'pièces complémentaires' : 'pièce complémentaire';
        $list  = implode("\n", array_map(fn ($p) => "• {$p}", $piecesList));

        return $this->compose('Complément reçu', [
            "Bonjour {$nomEtudiant},",
            "Nous avons bien reçu {$nb} {$label} pour votre dossier *{$reference}* :",
            $list,
            'Elles ont été transmises au secrétariat pour vérification.',
            "_Suivre votre dossier :_\n" . $this->urlSuivi() . "&ref={$reference}",
        ]);
    }

    public function templatePret(string $reference, string $typeLabel, string $nomEtudiant): string
    {
        return $this->compose('Document prêt', [
            "Bonjour {$nomEtudiant},",
            "Votre *{$typeLabel}* est prêt.",
            $this->fields(['Référence' => $reference]),
            "Vous pouvez le retirer au secrétariat du CAP aux heures d'ouverture.",
        ]);
    }

    public function templateRejete(string $reference, string $typeLabel, string $motif, string $nomEtudiant): string
    {
        return $this->compose('Demande non aboutie', [
            "Bonjour {$nomEtudiant},",
            "Votre demande de *{$typeLabel}* n'a pas pu aboutir.",
            $this->fields(['Référence' => $reference, 'Motif' => $motif]),
            "Pour toute question, rapprochez-vous du secrétariat du CAP.",
        ]);
    }

    public function templateSousReserve(string $reference, string $typeLabel, string $motif, string $nomEtudiant): string
    {
        return $this->compose('Pièce à compléter', [
            "Bonjour {$nomEtudiant},",
            "Votre demande de *{$typeLabel}* nécessite un complément.",
            $this->fields(['Référence' => $reference, 'Motif' => $motif]),
            "_Déposer les pièces demandées :_\n" . $this->urlComplement() . "&ref={$reference}",
        ]);
    }

    /**
     * Message libre de la secrétaire vers le demandeur (bouton « Contacter le demandeur »).
     */
    public function templateMessageSecretariat(string $nomDemandeur, string $message, string $secretaireName): string
    {
        return $this->compose('Message du secrétariat', [
            "Bonjour {$nomDemandeur},",
            $message,
            "_{$secretaireName}_\n_Secrétariat CAP-EPAC_",
        ]);
    }

    public function templateRemis(string $reference, string $typeLabel, string $nomEtudiant): string
    {
        return $this->compose('Document remis', [
            "Bonjour {$nomEtudiant},",
            "Votre *{$typeLabel}* vous a bien été remis.",
            $this->fields(['Référence' => $reference]),
            'Merci de votre confiance.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // TEMPLATES - PERSONNEL (STAFF)
    // ══════════════════════════════════════════════════════════════════════════

    public function templateNouvelleDemandeSecretaire(
        string $destinataireNom,
        string $reference,
        string $typeDocument,
        string $nomEtudiant,
        string $matricule,
    ): string {
        return $this->compose('Nouvelle demande', [
            "Bonjour {$destinataireNom},",
            'Une nouvelle demande vient d\'être déposée.',
            $this->fields([
                'Référence' => $reference,
                'Document'  => $typeDocument,
                'Étudiant'  => $this->student($nomEtudiant, $matricule),
            ]),
            'Merci de la traiter depuis votre tableau de bord.',
        ]);
    }

    public function templateComplementSecretaire(
        string $destinataireNom,
        string $reference,
        string $nomEtudiant,
        int    $nbPieces,
    ): string {
        $label = $nbPieces > 1 ? 'pièces complémentaires' : 'pièce complémentaire';
        return $this->compose('Complément déposé', [
            "Bonjour {$destinataireNom},",
            "*{$nomEtudiant}* a déposé {$nbPieces} {$label}.",
            $this->fields(['Référence' => $reference]),
            'Merci de vérifier ces nouveaux éléments.',
        ]);
    }

    /**
     * Transmission d'un dossier à un acteur du circuit.
     * Le rôle affiché ({$expediteurRole}) est déjà le libellé résolu via
     * WorkflowConstants::ROLE_LABELS par l'appelant (NotificationService) -
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
        return $this->compose('Dossier à traiter', [
            "Bonjour {$destinataireNom},",
            "Un dossier vous a été transmis par *{$expediteurRole}*.",
            $this->fields([
                'Référence' => $reference,
                'Document'  => $typeDocument,
                'Étudiant'  => $this->student($etudiantNom, $matricule),
                'Note'      => $commentaire,
            ]),
            'Merci de vous connecter pour le traiter.',
        ]);
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
        return $this->compose('Dossier renvoyé pour correction', [
            "Bonjour {$destinataireNom},",
            "Un dossier vous a été renvoyé par *{$expediteurRole}*.",
            $this->fields([
                'Référence' => $reference,
                'Document'  => $typeDocument,
                'Étudiant'  => $this->student($etudiantNom, $matricule),
                'Motif'     => $commentaire,
            ]),
            'Merci de le corriger puis de le renvoyer.',
        ]);
    }

    public function templateDossierDirection(
        string $destinataireNom,
        string $nomEtudiant,
        string $reference,
    ): string {
        return $this->compose('Dossier en Direction', [
            "Bonjour {$destinataireNom},",
            'Ce dossier est en cours de signature à la Direction.',
            $this->fields(['Référence' => $reference, 'Étudiant' => $nomEtudiant]),
            'Merci de préparer et d\'acheminer le document physique correspondant.',
        ]);
    }

    public function templateDirecteurSigne(
        string $destinataireNom,
        string $nomEtudiant,
        string $reference,
        string $typeDocument,
        string $matricule,
    ): string {
        return $this->compose('Signature du Directeur', [
            "Bonjour {$destinataireNom},",
            'Le *Directeur* a signé ce dossier.',
            $this->fields([
                'Référence' => $reference,
                'Document'  => $typeDocument,
                'Étudiant'  => $this->student($nomEtudiant, $matricule),
            ]),
            'Merci de préparer le document final puis de le marquer _prêt_ dans le système.',
        ]);
    }
}

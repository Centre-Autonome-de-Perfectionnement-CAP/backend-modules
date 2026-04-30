<?php

namespace App\Modules\Demandes\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

/**
 * Service d'envoi de messages WhatsApp via Twilio.
 *
 * Règles :
 *  - Jamais bloquant : tout échec est loggué silencieusement
 *  - Jamais d'exception propagée vers le workflow
 *  - Normalise automatiquement les numéros béninois (+229)
 *  - Journalise chaque envoi (succès + échec)
 *  - Aucun emoji — mise en forme WhatsApp (*gras*, _italique_)
 *
 * Formats acceptés en entrée :
 *   XXXXXXXX           → +22901XXXXXXXX  (8 chiffres → nouveau format béninois)
 *   01XXXXXXXX         → +22901XXXXXXXX  (10 chiffres commençant par 01)
 *   229XXXXXXXX        → +229XXXXXXXX    (11 chiffres, ancien format)
 *   22901XXXXXXXX      → +22901XXXXXXXX  (13 chiffres, nouveau format sans +)
 *   0022901XXXXXXXX    → +22901XXXXXXXX  (préfixe 00)
 *   00229XXXXXXXX      → +229XXXXXXXX    (préfixe 00, ancien format)
 *   +229XXXXXXXX       → inchangé
 *   +22901XXXXXXXX     → inchangé
 *   Séparateurs (espaces, tirets, points) tolérés entre chiffres.
 *
 * NOTE SANDBOX TWILIO :
 *   Le sandbox Twilio WhatsApp distingue +229XXXXXXXX et +22901XXXXXXXX
 *   comme deux numéros différents. Si un destinataire a fait "join" depuis
 *   son ancien numéro (+229XXXXXXXX) mais que le formulaire envoie le nouveau
 *   format (+22901XXXXXXXX), le message sera rejeté (error 63015).
 *   → Solution définitive : passer en production Twilio (WhatsApp Business API).
 *   → Solution temporaire sandbox : les destinataires doivent "join" depuis le
 *     numéro EXACTEMENT tel qu'il sera envoyé (format normalisé affiché dans le form).
 */
class WhatsAppService
{
    private ?TwilioClient $client = null;

    private const DIVIDER = '――――――――――――――――――';

    // ── Envoi principal ───────────────────────────────────────────────────────

    public function send(string $phone, string $message, string $context = ''): bool
    {
        $normalized = $this->normalizePhone($phone);

        if (!$normalized) {
            Log::warning('[WhatsApp] Numéro invalide ou absent', [
                'phone'   => $phone,
                'context' => $context,
            ]);
            return false;
        }

        try {
            $this->client()->messages->create(
                "whatsapp:{$normalized}",
                [
                    'from' => config('services.twilio.whatsapp_from'),
                    'body' => $message,
                ]
            );

            Log::info('[WhatsApp] Message envoyé', [
                'to'      => $normalized,
                'context' => $context,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('[WhatsApp] Échec envoi', [
                'to'      => $normalized,
                'context' => $context,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ── Normalisation numéro ──────────────────────────────────────────────────

    public function normalizePhone(string $phone): ?string
    {
        // Supprimer séparateurs (espaces, tirets, points, parenthèses)
        $clean   = preg_replace('/[\s\-.()\t]/', '', trim($phone));
        $digits  = preg_replace('/\D/', '', $clean);

        if (strlen($digits) < 8) {
            return null;
        }

        // Préfixe 00 → enlever les deux zéros
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $this->normalizeDigits($digits);
    }

    private function normalizeDigits(string $digits): ?string
    {
        return match (strlen($digits)) {
            8 => '+229' . $digits,                                         // XXXXXXXX → nouveau format béninois
            10 => str_starts_with($digits, '01') ? '+229' . $digits : null,  // 01XXXXXXXX → +22901XXXXXXXX
            11 => str_starts_with($digits, '229') ? '+' . $digits : null,    // 229XXXXXXXX → +229XXXXXXXX (ancien)
            13 => str_starts_with($digits, '22901') ? '+' . $digits : null,  // 22901XXXXXXXX → +22901XXXXXXXX
            default => null,
        };
    }

    // ── Client Twilio (lazy) ──────────────────────────────────────────────────

    private function client(): TwilioClient
    {
        if (!$this->client) {
            $this->client = new TwilioClient(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
        }
        return $this->client;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // TEMPLATES — ÉTUDIANT
    // ═════════════════════════════════════════════════════════════════════════

    public function templateSoumission(string $reference, string $typeLabel, string $email): string
    {
        $suiviUrl = config('app.url') . '/app-cap/student-services?ref=' . $reference;
        return implode("\n", [
            "✅ *Demande Reçue*",
            "",
            "Votre demande de *{$typeLabel}* (Réf: {$reference}) a bien été enregistrée.",
            "",
            "Votre dossier est en cours d'examen. Vous serez notifié(e) à chaque étape.",
            "",
            "Suivez l'avancement ici : {$suiviUrl}"
        ]);
    }

    public function templateComplementEtudiant(string $reference, array $piecesList): string
    {
        $nb = count($piecesList);
        $label = $nb <= 1 ? 'pièce complémentaire reçue' : 'pièces complémentaires reçues';
        $suiviUrl = config('app.url') . '/app-cap/student-services?ref=' . $reference;

        $lines = [
            "📎 *Complément Reçu*",
            "",
            "Pour votre demande (Réf: {$reference}), nous avons bien reçu {$nb} {$label} :",
        ];

        foreach ($piecesList as $piece) {
            $lines[] = "- {$piece}";
        }

        $lines[] = "";
        $lines[] = "Elles ont été transmises au secrétariat pour vérification.";
        $lines[] = "";
        $lines[] = "Suivez l'avancement ici : {$suiviUrl}";

        return implode("\n", $lines);
    }

    public function templatePret(string $reference, string $typeLabel): string
    {
        return implode("\n", [
            "🎉 *Document Prêt*",
            "",
            "Votre demande de *{$typeLabel}* (Réf: {$reference}) a été traitée avec succès.",
            "",
            "Vous pouvez venir récupérer votre document au secrétariat durant les heures d'ouverture.",
        ]);
    }

    public function templateRejete(string $reference, string $typeLabel, string $motif): string
    {
        return implode("\n", [
            "❌ *Demande Rejetée*",
            "",
            "Votre demande de *{$typeLabel}* (Réf: {$reference}) n'a pas pu aboutir.",
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
            "⚠️ *Dossier Sous Réserve*",
            "",
            "Votre demande de *{$typeLabel}* (Réf: {$reference}) est en cours de traitement mais nécessite votre attention.",
            "",
            "*Motif :* {$motif}",
            "",
            "Veuillez régulariser la situation en soumettant un complément de dossier en ligne.",
            "",
            "Suivez l'avancement et complétez ici : {$suiviUrl}"
        ]);
    }

    public function templateRemis(string $reference, string $typeLabel): string
    {
        return implode("\n", [
            "🤝 *Document Retiré*",
            "",
            "Votre document *{$typeLabel}* (Réf: {$reference}) vous a été remis avec succès.",
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
            "📥 *Nouvelle Demande Reçue*",
            "",
            "Bonjour *{$destinataireNom}*,",
            "Une nouvelle demande de *{$typeDocument}* a été soumise par *{$nomEtudiant}* (Réf: {$reference}).",
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
            "📎 *Complément de Dossier*",
            "",
            "Bonjour *{$destinataireNom}*,",
            "L'étudiant(e) *{$nomEtudiant}* (Réf: {$reference}) vient de déposer {$nbPieces} {$label}.",
            "",
            "Veuillez vérifier les nouveaux documents.",
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
            "📁 *Nouveau Dossier à Traiter*",
            "",
            "Bonjour *{$destinataireNom}*,",
            "*[{$expediteurRole}] {$expediteurNom}* vient de vous transmettre la demande de *{$etudiantNom}* (Réf: {$reference} - {$typeDocument}).",
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
            "⚠️ *Dossier Renvoyé pour Correction*",
            "",
            "Bonjour *{$destinataireNom}*,",
            "Le dossier de *{$etudiantNom}* (Réf: {$reference}) vous a été renvoyé par *[{$expediteurRole}] {$expediteurNom}*.",
            "",
        ];

        if ($commentaire) {
            $lines[] = "*Motif :* {$commentaire}";
        }

        $lines[] = "Veuillez vous connecter pour corriger la demande.";

        return implode("\n", $lines);
    }
}

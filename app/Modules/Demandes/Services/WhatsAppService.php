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
        $appName = config('app.name', 'CAP-EPAC');
        return implode("\n", [
            "*{$appName}*",
            self::DIVIDER,
            '',
            'Votre demande a bien été reçue.',
            '',
            "*Type      :* {$typeLabel}",
            "*Référence :* {$reference}",
            "*E-mail    :* {$email}",
            '',
            self::DIVIDER,
            'Votre dossier est en cours d\'examen.',
            'Vous serez notifié(e) par WhatsApp et e-mail à chaque étape importante.',
            '',
            '_Ne répondez pas à ce message._',
        ]);
    }

    public function templateComplementEtudiant(string $reference, array $piecesList): string
    {
        $appName = config('app.name', 'CAP-EPAC');
        $nb      = count($piecesList);
        $label   = $nb <= 1 ? 'pièce déposée' : 'pièces déposées';

        $lines = [
            "*{$appName}*",
            self::DIVIDER,
            '',
            'Complément de dossier reçu.',
            '',
            "*Référence :* {$reference}",
            "*{$nb} {$label}*",
            '',
        ];

        foreach ($piecesList as $piece) {
            $lines[] = "  - {$piece}";
        }

        $lines[] = '';
        $lines[] = self::DIVIDER;
        $lines[] = 'Vos pièces ont été transmises au secrétariat pour traitement.';
        $lines[] = '';
        $lines[] = '_Ne répondez pas à ce message._';

        return implode("\n", $lines);
    }

    public function templatePret(string $reference, string $typeLabel): string
    {
        $appName = config('app.name', 'CAP-EPAC');
        return implode("\n", [
            "*{$appName}*",
            self::DIVIDER,
            '',
            'Votre document est *prêt* à être retiré.',
            '',
            "*Type      :* {$typeLabel}",
            "*Référence :* {$reference}",
            '',
            self::DIVIDER,
            'Vous pouvez venir le récupérer au secrétariat durant les heures d\'ouverture.',
            '',
            '_Ne répondez pas à ce message._',
        ]);
    }

    public function templateRejete(string $reference, string $typeLabel, string $motif): string
    {
        $appName = config('app.name', 'CAP-EPAC');
        return implode("\n", [
            "*{$appName}*",
            self::DIVIDER,
            '',
            'Votre demande a été *rejetée*.',
            '',
            "*Type      :* {$typeLabel}",
            "*Référence :* {$reference}",
            "*Motif     :* {$motif}",
            '',
            self::DIVIDER,
            'Veuillez vous rapprocher du secrétariat pour plus d\'informations.',
            '',
            '_Ne répondez pas à ce message._',
        ]);
    }

    public function templateRemis(string $reference, string $typeLabel): string
    {
        $appName = config('app.name', 'CAP-EPAC');
        return implode("\n", [
            "*{$appName}*",
            self::DIVIDER,
            '',
            'Votre document vous a été *remis*.',
            '',
            "*Type      :* {$typeLabel}",
            "*Référence :* {$reference}",
            '',
            self::DIVIDER,
            'Merci de votre confiance. Bonne continuation !',
            '',
            '_Ne répondez pas à ce message._',
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
        $appName = config('app.name', 'CAP-EPAC');
        $url     = config('app.url') . '/dashboard';
        return implode("\n", [
            "*{$appName} — Nouvelle demande*",
            self::DIVIDER,
            '',
            "Bonjour *{$destinataireNom}*,",
            '',
            'Une nouvelle demande de document a été soumise.',
            '',
            "*Document   :* {$typeDocument}",
            "*Référence  :* {$reference}",
            "*Étudiant(e):* {$nomEtudiant}",
            "*Matricule  :* {$matricule}",
            '',
            self::DIVIDER,
            "Connectez-vous sur *{$url}* pour traiter ce dossier.",
            '',
            '_Ne répondez pas à ce message._',
        ]);
    }

    public function templateComplementSecretaire(
        string $destinataireNom,
        string $reference,
        string $nomEtudiant,
        int    $nbPieces,
    ): string {
        $appName = config('app.name', 'CAP-EPAC');
        $url     = config('app.url') . '/dashboard';
        $label   = $nbPieces <= 1 ? 'pièce complémentaire' : 'pièces complémentaires';
        return implode("\n", [
            "*{$appName} — Nouveau complément de dossier*",
            self::DIVIDER,
            '',
            "Bonjour *{$destinataireNom}*,",
            '',
            'Un(e) étudiant(e) vient de déposer un complément de dossier.',
            '',
            "*Étudiant(e):* {$nomEtudiant}",
            "*Référence  :* {$reference}",
            "*Pièces     :* {$nbPieces} {$label}",
            '',
            self::DIVIDER,
            "Connectez-vous sur *{$url}* pour consulter et traiter ce dossier.",
            '',
            '_Ne répondez pas à ce message._',
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
        $appName = config('app.name', 'CAP-EPAC');
        $url     = config('app.url') . '/dashboard';

        $lines = [
            "*{$appName} — Dossier à traiter*",
            self::DIVIDER,
            '',
            "Bonjour *{$destinataireNom}*,",
            '',
            "Un dossier vous a été transmis par *{$expediteurNom}* ({$expediteurRole}).",
            '',
            "*Document   :* {$typeDocument}",
            "*Référence  :* {$reference}",
            "*Étudiant(e):* {$etudiantNom}",
        ];

        if ($matricule) $lines[] = "*Matricule  :* {$matricule}";
        if ($commentaire) $lines[] = "*Commentaire:* {$commentaire}";

        $lines[] = '';
        $lines[] = self::DIVIDER;
        $lines[] = "Connectez-vous sur *{$url}* pour traiter ce dossier.";
        $lines[] = '';
        $lines[] = '_Ne répondez pas à ce message._';

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
        $appName = config('app.name', 'CAP-EPAC');
        $url     = config('app.url') . '/dashboard';

        $lines = [
            "*{$appName} — Dossier en correction*",
            self::DIVIDER,
            '',
            "Bonjour *{$destinataireNom}*,",
            '',
            "Un dossier vous a été renvoyé pour correction par *{$expediteurNom}* ({$expediteurRole}).",
            '',
            "*Document   :* {$typeDocument}",
            "*Référence  :* {$reference}",
            "*Étudiant(e):* {$etudiantNom}",
        ];

        if ($matricule) $lines[] = "*Matricule  :* {$matricule}";
        if ($commentaire) $lines[] = "*Motif      :* {$commentaire}";

        $lines[] = '';
        $lines[] = self::DIVIDER;
        $lines[] = "Connectez-vous sur *{$url}* pour traiter ce dossier.";
        $lines[] = '';
        $lines[] = '_Ne répondez pas à ce message._';

        return implode("\n", $lines);
    }
}

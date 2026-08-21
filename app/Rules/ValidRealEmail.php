<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRealEmail implements ValidationRule
{
    /**
     * Liste des domaines d'emails temporaires / jetables connus.
     */
    protected static array $disposableDomains = [
        'yopmail.com', 'yopmail.fr', 'yopmail.net', 'cool.fr.nf', 'jetable.fr.nf', 'courriel.fr.nf',
        'moncourrier.fr.nf', 'monemail.fr.nf', 'monmail.fr.nf', 'tempmail.com', 'temp-mail.org',
        '10minutemail.com', '10minutemail.net', 'guerrillamail.com', 'guerrillamail.net',
        'mailinator.com', 'throwawaymail.com', 'trashmail.com', 'getairmail.com', 'sharklasers.com',
        'dispostable.com', 'crazymailing.com', 'mohmal.com', 'fakemailgenerator.com', 'mytemp.email',
        'generator.email', 'dropmail.me', 'tempail.com', 'burnermail.io', 'emailondeck.com',
    ];

    /**
     * Suggestions pour les fautes de frappe de domaines courants.
     */
    protected static array $typoSuggestions = [
        'gmil.com' => 'gmail.com',
        'gmai.com' => 'gmail.com',
        'gmial.com' => 'gmail.com',
        'gmaill.com' => 'gmail.com',
        'gamil.com' => 'gmail.com',
        'gnail.com' => 'gmail.com',
        'yaho.com' => 'yahoo.com',
        'yaho.fr' => 'yahoo.fr',
        'yahou.fr' => 'yahoo.fr',
        'yahou.com' => 'yahoo.com',
        'hotmial.com' => 'hotmail.com',
        'hotmai.com' => 'hotmail.com',
        'hotmaill.com' => 'hotmail.com',
        'outlok.com' => 'outlook.com',
        'outllok.com' => 'outlook.com',
    ];

    /**
     * Exécute la règle de validation.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $email = trim((string) $value);

        // 1. Validation de la syntaxe de base
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail("L'adresse email '{$email}' n'a pas un format valide.");
            return;
        }

        // 2. Découpage identifiant / domaine
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            $fail("L'adresse email est invalide.");
            return;
        }

        $domain = mb_strtolower(trim($parts[1]));

        // 3. Vérification de la présence d'une extension de domaine (TLD) valide
        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
            $fail("Le domaine de l'adresse email ({$domain}) n'est pas valide.");
            return;
        }

        // 4. Détection de fautes de frappe courantes
        if (isset(self::$typoSuggestions[$domain])) {
            $suggested = self::$typoSuggestions[$domain];
            $fail("Le domaine '{$domain}' semble comporter une faute de frappe. Vouliez-vous dire '@{$suggested}' ?");
            return;
        }

        // 5. Rejet des emails temporaires / jetables
        if (in_array($domain, self::$disposableDomains, true)) {
            $fail("Les adresses emails temporaires / jetables (@{$domain}) ne sont pas autorisées pour les démarches académiques.");
            return;
        }

        // 6. Vérification DNS en direct (MX ou A record)
        if (!self::domainExists($domain)) {
            $fail("Le domaine de messagerie '@{$domain}' n'existe pas ou ne peut pas recevoir d'emails.");
            return;
        }
    }

    /**
     * Vérifie si un nom de domaine possède des enregistrements MX ou A valides.
     */
    public static function domainExists(string $domain): bool
    {
        // En local ou environnement de test, si la résolution DNS échoue à cause du réseau local
        // on tente MX puis A
        try {
            if (function_exists('checkdnsrr')) {
                return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            }
        } catch (\Throwable $e) {
            // En cas d'erreur de résolution imprévue, fallback tolérant
            return true;
        }

        return true;
    }

    /**
     * Analyse complète d'une adresse email pour l'endpoint API de validation.
     */
    public static function analyzeEmail(string $email): array
    {
        $email = trim($email);

        if (empty($email)) {
            return [
                'valid' => false,
                'message' => "L'adresse email est requise.",
                'suggestion' => null,
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => "Le format de l'adresse email est invalide (ex: nom@domaine.com).",
                'suggestion' => null,
            ];
        }

        $parts = explode('@', $email);
        $user = $parts[0];
        $domain = mb_strtolower(trim($parts[1] ?? ''));

        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
            return [
                'valid' => false,
                'message' => "L'extension du domaine (@{$domain}) est incomplète ou invalide.",
                'suggestion' => null,
            ];
        }

        if (isset(self::$typoSuggestions[$domain])) {
            $suggested = self::$typoSuggestions[$domain];
            return [
                'valid' => false,
                'message' => "Attention à la faute de frappe : vouliez-vous écrire '{$user}@{$suggested}' ?",
                'suggestion' => "{$user}@{$suggested}",
            ];
        }

        if (in_array($domain, self::$disposableDomains, true)) {
            return [
                'valid' => false,
                'message' => "Les adresses emails temporaires (@{$domain}) ne sont pas acceptées.",
                'suggestion' => null,
            ];
        }

        if (!self::domainExists($domain)) {
            return [
                'valid' => false,
                'message' => "Le serveur de messagerie '@{$domain}' n'existe pas ou ne peut pas recevoir d'emails.",
                'suggestion' => null,
            ];
        }

        return [
            'valid' => true,
            'message' => "Adresse email valide et domaine actif.",
            'domain' => $domain,
            'suggestion' => null,
        ];
    }
}

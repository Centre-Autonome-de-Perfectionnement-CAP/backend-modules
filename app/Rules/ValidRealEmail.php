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
     * Liste des identifiants (local part) fictifs ou bidons.
     */
    protected static array $dummyUsernames = [
        'test', 'test1', 'test12', 'test123', 'testing', 'admin', 'administrator', 'user', 'user1',
        'temp', 'demo', 'fake', 'sample', 'example', 'dummy', 'null', 'undefined', 'none', 'nobody',
        'noone', 'anonymous', 'azerty', 'qwerty', 'asdf', 'toto', 'tata', 'titi', 'tutu', 'aaaa',
        'bbbb', 'cccc', '1111', '1234', '12345', '123456', 'email', 'mail', 'monmail', 'nom',
        'prenom', 'xyz', 'abc', 'xxx', 'abcde', 'aaaaaa', '111111', 'testeur', 'inconnu'
    ];

    /**
     * Exécute la règle de validation.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $res = self::analyzeEmail((string) $value);
        if (!$res['valid']) {
            $fail($res['message']);
        }
    }

    /**
     * Vérifie si un nom de domaine possède des enregistrements MX ou A valides.
     */
    public static function domainExists(string $domain): bool
    {
        try {
            if (function_exists('checkdnsrr')) {
                return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            }
        } catch (\Throwable $e) {
            return true;
        }

        return true;
    }

    /**
     * Analyse complète et approfondie de la validité et de l'existence d'une adresse email.
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

        // 1. Validation de la syntaxe RFC de base
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => "Le format de l'adresse email est invalide (ex: nom@domaine.com).",
                'suggestion' => null,
            ];
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return [
                'valid' => false,
                'message' => "L'adresse email est incomplète.",
                'suggestion' => null,
            ];
        }

        $user = trim($parts[0]);
        $domain = mb_strtolower(trim($parts[1] ?? ''));
        $userLower = mb_strtolower($user);

        // 2. Vérification de la longueur minimale générale de l'identifiant
        if (mb_strlen($user) < 3) {
            return [
                'valid' => false,
                'message' => "L'identifiant '{$user}' est trop court. Une adresse email valide comporte au moins 3 caractères avant le '@'.",
                'suggestion' => null,
            ];
        }

        // 3. Vérification des identifiants fictifs / bidons évidents
        if (in_array($userLower, self::$dummyUsernames, true)) {
            return [
                'valid' => false,
                'message' => "L'adresse '{$email}' semble être une adresse fictive ou de test. Veuillez renseigner votre véritable adresse email.",
                'suggestion' => null,
            ];
        }

        // 4. Détection de répétitions abusives (ex: aaaaaa@...)
        if (preg_match('/^(.)\1{4,}$/', $userLower)) {
            return [
                'valid' => false,
                'message' => "L'adresse email contient une répétition anormale de caractères. Veuillez saisir une adresse valide.",
                'suggestion' => null,
            ];
        }

        // 5. Règles spécifiques des grands fournisseurs de messagerie
        // GMAIL / GOOGLEMAIL
        if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
            if (mb_strlen($user) < 6 || mb_strlen($user) > 30) {
                return [
                    'valid' => false,
                    'message' => "Une adresse Gmail doit comporter entre 6 et 30 caractères avant '@{$domain}' (votre saisie: " . mb_strlen($user) . " car.).",
                    'suggestion' => null,
                ];
            }
            if (!preg_match('/^[a-z0-9.]+$/i', $user)) {
                return [
                    'valid' => false,
                    'message' => "Une adresse Gmail ne peut contenir que des lettres, des chiffres et des points.",
                    'suggestion' => null,
                ];
            }
            if (str_starts_with($user, '.') || str_ends_with($user, '.')) {
                return [
                    'valid' => false,
                    'message' => "Une adresse Gmail ne peut pas commencer ni se terminer par un point.",
                    'suggestion' => null,
                ];
            }
        }

        // YAHOO
        if (str_contains($domain, 'yahoo.') || $domain === 'ymail.com' || $domain === 'rocketmail.com') {
            if (mb_strlen($user) < 4 || mb_strlen($user) > 32) {
                return [
                    'valid' => false,
                    'message' => "Une adresse Yahoo doit comporter entre 4 et 32 caractères avant '@{$domain}'.",
                    'suggestion' => null,
                ];
            }
            if (!preg_match('/^[a-z]/i', $user)) {
                return [
                    'valid' => false,
                    'message' => "Une adresse Yahoo doit obligatoirement commencer par une lettre.",
                    'suggestion' => null,
                ];
            }
        }

        // MICROSOFT (Outlook, Hotmail, Live, MSN)
        if (in_array($domain, ['outlook.com', 'outlook.fr', 'hotmail.com', 'hotmail.fr', 'live.com', 'live.fr', 'msn.com'], true)) {
            if (mb_strlen($user) < 3 || mb_strlen($user) > 64) {
                return [
                    'valid' => false,
                    'message' => "Une adresse Microsoft/Outlook doit comporter au moins 3 caractères avant '@{$domain}'.",
                    'suggestion' => null,
                ];
            }
            if (!preg_match('/^[a-z]/i', $user)) {
                return [
                    'valid' => false,
                    'message' => "Une adresse Microsoft/Outlook doit commencer par une lettre.",
                    'suggestion' => null,
                ];
            }
        }

        // 6. Vérification de l'extension de domaine TLD
        if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
            return [
                'valid' => false,
                'message' => "L'extension du domaine (@{$domain}) est incomplète ou invalide.",
                'suggestion' => null,
            ];
        }

        // 7. Détection des fautes de frappe de domaines courants
        if (isset(self::$typoSuggestions[$domain])) {
            $suggested = self::$typoSuggestions[$domain];
            return [
                'valid' => false,
                'message' => "Attention à la faute de frappe : vouliez-vous écrire '{$user}@{$suggested}' ?",
                'suggestion' => "{$user}@{$suggested}",
            ];
        }

        // 8. Rejet des emails temporaires / jetables
        if (in_array($domain, self::$disposableDomains, true)) {
            return [
                'valid' => false,
                'message' => "Les adresses emails temporaires / jetables (@{$domain}) ne sont pas acceptées.",
                'suggestion' => null,
            ];
        }

        // 9. Vérification DNS de l'existence réelle du domaine (MX / A)
        if (!self::domainExists($domain)) {
            return [
                'valid' => false,
                'message' => "Le serveur de messagerie '@{$domain}' n'existe pas sur Internet ou ne peut pas recevoir d'emails.",
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

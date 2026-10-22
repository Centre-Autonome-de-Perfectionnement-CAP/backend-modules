<?php

namespace App\Modules\Stages\Services;

/**
 * Code de vérification du cachet numérique : 8 caractères lisibles
 * (sans 0/O, 1/I/L), présentés « XXXX-XXXX ».
 */
final class StageNoteCode
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        $c = '';
        for ($i = 0; $i < 8; $i++) {
            $c .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return substr($c, 0, 4) . '-' . substr($c, 4);
    }

    /** Saisie libre (minuscules, espaces, sans tiret) → forme canonique, ou null. */
    public static function normalize(?string $input): ?string
    {
        $raw = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));
        if (strlen($raw) !== 8 || strspn($raw, self::ALPHABET) !== 8) {
            return null;
        }

        return substr($raw, 0, 4) . '-' . substr($raw, 4);
    }
}

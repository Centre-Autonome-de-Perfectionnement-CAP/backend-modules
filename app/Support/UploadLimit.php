<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Taille maximale des fichiers envoyés, la même partout :
 *  - 2 Mo par fichier (pièces, photos, justificatifs, documents) ;
 *  - 10 Mo pour un rapport de stage ou un mémoire ;
 *  - 20 Mo pour un support de cours.
 * Les photos prises au téléphone sont compressées avant l'envoi (site).
 * PHP doit accepter la plus grande limite (public/.user.ini).
 */
final class UploadLimit
{
    public const DEFAULT_KB = 2048;
    public const REPORT_KB  = 10240;
    public const COURSE_KB  = 20480;

    /** Règle de validation (« max:2048 »). */
    public static function rule(int $kb = self::DEFAULT_KB): string
    {
        return 'max:' . $kb;
    }

    /** « 2 Mo », « 10 Mo »… */
    public static function label(int $kb = self::DEFAULT_KB): string
    {
        return rtrim(rtrim(number_format($kb / 1024, 1, ',', ''), '0'), ',') . ' Mo';
    }

    public static function message(int $kb = self::DEFAULT_KB): string
    {
        return 'Fichier trop lourd : ' . self::label($kb) . ' maximum.';
    }

    /**
     * Un seul message, compréhensible, pour tout fichier trop lourd (et pour
     * un fichier refusé par PHP avant même Laravel). Les messages propres à
     * un formulaire restent prioritaires.
     */
    public static function registerMessages(): void
    {
        Validator::replacer('max', function (string $message, string $attribute, string $rule, array $parameters, $validator) {
            return data_get($validator->getData(), $attribute) instanceof UploadedFile && str_contains($message, 'kilobytes')
                ? self::message((int) $parameters[0])
                : str_replace(':max', $parameters[0], $message);
        });
        Validator::replacer('uploaded', fn (string $message) => str_contains($message, 'failed to upload')
            ? 'Le fichier n\'a pas pu être envoyé : il est sans doute trop lourd (' . self::label() . ' maximum).'
            : $message);
    }
}

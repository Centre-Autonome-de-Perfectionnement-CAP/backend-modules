<?php

namespace App\Modules\Core\Support;

use Closure;
use Illuminate\Http\UploadedFile;

/**
 * Règles de validation des fichiers bureautiques envoyés par les usagers
 * (Word, PowerPoint, Excel, PDF, ZIP, images).
 *
 * Le type détecté par le serveur varie selon le téléphone et le logiciel qui
 * a produit le fichier (un .doc ou un .xls ancien est souvent vu comme
 * « application/vnd.ms-office » ou « application/CDFV2 », un .docx comme
 * « application/zip » ou « application/octet-stream ») : une liste blanche
 * de types refusait ainsi des fichiers valides. On contrôle donc l'extension
 * (liste blanche) et on refuse seulement les contenus dangereux (pages web,
 * scripts, programmes). Les fichiers restent privés et ne sont jamais
 * exécutés ni affichés tels quels par le serveur.
 */
final class OfficeFile
{
    private const DANGEROUS = [
        'text/html', 'application/xhtml+xml', 'image/svg+xml', 'text/javascript', 'application/javascript',
        'application/x-php', 'text/x-php', 'application/x-httpd-php', 'text/x-shellscript', 'application/x-sh',
        'application/x-dosexec', 'application/x-msdownload', 'application/x-executable', 'application/x-elf', 'application/x-mach-binary',
    ];

    /** @param list<string> $extensions */
    public static function rules(array $extensions, int $maxKb): array
    {
        return ['file', 'max:' . $maxKb, 'extensions:' . implode(',', $extensions),
            static function (string $attribute, mixed $value, Closure $fail): void {
                if ($value instanceof UploadedFile && in_array(strtolower((string) $value->getMimeType()), self::DANGEROUS, true)) {
                    $fail('Ce fichier n\'est pas accepté.');
                }
            },
        ];
    }
}

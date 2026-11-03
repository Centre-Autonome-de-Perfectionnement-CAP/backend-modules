<?php

namespace App\Modules\Core\Support;

/**
 * Dossier des polices de dompdf.
 *
 * dompdf écrit dans son dossier de polices (métriques .ufm, caches .json).
 * En production, le site (php-fpm) et la file d'attente ne tournent pas sous
 * le même utilisateur : un dossier commun finit avec des fichiers que l'un ne
 * peut plus réécrire (« fopen(...) Permission denied », PDF impossible).
 *
 * Les polices sont livrées avec le code (resources/pdf-fonts, déjà
 * enregistrées : aucune @font-face à analyser) puis copiées dans un dossier
 * propre à chaque utilisateur système. Chacun n'écrit que dans le sien, et une
 * nouvelle version des polices donne un nouveau dossier.
 */
final class PdfFonts
{
    private const SOURCE = 'resources/pdf-fonts';
    private const MARKER = '.pret';

    private static ?string $dir = null;

    public static function dir(): string
    {
        if (self::$dir !== null && is_file(self::$dir . '/' . self::MARKER)) {
            return self::$dir;
        }

        $source = base_path(self::SOURCE);
        $files  = array_values(array_filter(scandir($source) ?: [], fn ($f) => is_file("$source/$f")));
        $sign   = substr(md5(implode('|', array_map(fn ($f) => $f . ':' . filesize("$source/$f"), $files))), 0, 10);
        $user   = function_exists('posix_geteuid') ? (string) posix_geteuid() : preg_replace('/\W/', '', get_current_user());

        $last = null;
        foreach ([sys_get_temp_dir(), storage_path('framework/cache')] as $base) {
            $dir = rtrim($base, '/') . "/cap-pdf-fonts-{$user}-{$sign}";
            try {
                self::seed($source, $files, $dir);

                return self::$dir = $dir;
            } catch (\RuntimeException $e) {
                $last = $e;
            }
        }

        throw $last ?? new \RuntimeException('Dossier des polices PDF indisponible.');
    }

    /** Copie complète dans un dossier temporaire, puis renommage : jamais de dossier à moitié rempli. */
    private static function seed(string $source, array $files, string $dir): void
    {
        if (is_file("$dir/" . self::MARKER)) {
            return;
        }
        $tmp = $dir . '.' . bin2hex(random_bytes(4));
        if (!@mkdir($tmp, 0700, true)) {
            throw new \RuntimeException("Création impossible : $tmp");
        }
        foreach ($files as $f) {
            if (!@copy("$source/$f", "$tmp/$f")) {
                self::remove($tmp);
                throw new \RuntimeException("Copie impossible : $f");
            }
        }
        touch("$tmp/" . self::MARKER);

        if (!@rename($tmp, $dir)) {
            // Un autre processus du même utilisateur l'a préparé entre-temps.
            self::remove($tmp);
            if (!is_file("$dir/" . self::MARKER)) {
                throw new \RuntimeException("Dossier des polices inutilisable : $dir");
            }
        }
    }

    private static function remove(string $dir): void
    {
        foreach (glob("$dir/{,.}*", GLOB_BRACE) ?: [] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir($dir);
    }
}

<?php

namespace App\Modules\Core\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;

/**
 * Contenu d'un fichier pour le visualiseur du progiciel, en JSON (base64) :
 * une réponse JSON n'est jamais interceptée par les gestionnaires de
 * téléchargement (Internet Download Manager…), contrairement à un PDF.
 *
 *  - sans « from » : le fichier entier (comportement d'origine) ;
 *  - avec « from » (et « size ») : une tranche seulement. Le visualiseur
 *    affiche ainsi la première page d'un PDF dès les premières tranches,
 *    puis charge la suite en arrière-plan.
 * « total » (taille du fichier) accompagne toujours la réponse.
 */
final class FilePreview
{
    public const CHUNK_DEFAULT = 262144;   // 256 Ko
    public const CHUNK_MAX     = 2097152;  // 2 Mo

    /** @return array{name: string, mime: string, total: int, content: string, from?: int} */
    public static function payload(Request $request, Filesystem $disk, string $path, string $name, ?string $mime = null): array
    {
        $mime  = $mime ?? ($disk->mimeType($path) ?: 'application/octet-stream');
        $total = (int) $disk->size($path);
        $base  = ['name' => $name, 'mime' => $mime, 'total' => $total];

        if ($request->query('from') === null) {
            return $base + ['content' => base64_encode((string) $disk->get($path))];
        }

        $from = max(0, (int) $request->query('from'));
        $size = min(self::CHUNK_MAX, max(1, (int) $request->query('size', self::CHUNK_DEFAULT)));
        $data = '';
        if ($from < $total) {
            $stream = $disk->readStream($path);
            if ($stream === null) {
                throw new \RuntimeException('Lecture du fichier impossible.');
            }
            try {
                fseek($stream, $from);
                $data = (string) stream_get_contents($stream, $size);
            } finally {
                fclose($stream);
            }
        }

        return $base + ['from' => $from, 'content' => base64_encode($data)];
    }
}

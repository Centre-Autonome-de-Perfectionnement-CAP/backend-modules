<?php

namespace App\Modules\Inscription\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FileService
{
    /**
     * Résout un fichier legacy depuis la table `files`.
     *
     * CORRECTIF :
     * 1. Le code ajoutait le préfixe 'files/' devant file_path, alors que
     *    file_path est déjà le chemin relatif complet (ex: dossiersingenierie/2025/11/xxx.pdf).
     * 2. Le disk utilisé était toujours 'public', alors que la colonne `disk`
     *    de la table files indique le disk réel (souvent 'public_files').
     *    On tente d'abord le disk enregistré, puis 'public' en fallback pour
     *    les anciens enregistrements sans disk renseigné.
     */
    public function getLegacyFile(string $path): ?array
    {
        $cleanPath = str_starts_with($path, 'public/') ? substr($path, 7) : $path;

        if (!is_numeric($cleanPath)) {
            return null;
        }

        $file = DB::table('files')->where('id', $cleanPath)->first();

        if (!$file) {
            return null;
        }

        $filePath   = $file->file_path;
        $diskName   = !empty($file->disk) ? $file->disk : 'public';
        $disksToTry = array_unique([$diskName, 'public', 'public_files']);

        foreach ($disksToTry as $disk) {
            try {
                $storage = Storage::disk($disk);
                if ($storage->exists($filePath)) {
                    return [
                        'path'     => $filePath,
                        'disk'     => $disk,
                        'mimeType' => $file->mime_type ?? $storage->mimeType($filePath),
                    ];
                }
            } catch (\Throwable) {
                // disk inexistant ou inaccessible — on essaie le suivant
            }
        }

        return null;
    }
}

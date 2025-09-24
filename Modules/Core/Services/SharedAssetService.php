<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SharedAssetService
{
    public function storeFile($file, $moduleName, $type = 'public')
    {
        try {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = "{$moduleName}/{$type}/{$filename}";

            Storage::disk('shared')->put($path, file_get_contents($file));

            return [
                'success' => true,
                'path' => $path,
                'url' => $this->getFileUrl($path)
            ];
        } catch (\Exception $e) {
            Log::error("Erreur stockage fichier: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getFileUrl($path)
    {
        return Storage::disk('shared')->url($path);
    }

    public function deleteFile($path)
    {
        return Storage::disk('shared')->delete($path);
    }
}

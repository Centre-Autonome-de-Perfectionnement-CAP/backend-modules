<?php

namespace App\Modules\Inscription\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FileService
{
    public function getLegacyFile(string $path): ?array
    {
        $cleanPath = str_starts_with($path, 'public/') ? substr($path, 7) : $path;
        
        if (is_numeric($cleanPath)) {
            $file = DB::table('files')->where('id', $cleanPath)->first();
            if ($file) {
                $foundPath = 'files/' . $file->file_path;
                if (Storage::disk('public')->exists($foundPath)) {
                    return [
                        'path' => $foundPath,
                        'mimeType' => Storage::disk('public')->mimeType($foundPath)
                    ];
                }
            }
        }
        
        return null;
    }
}

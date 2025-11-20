<?php

namespace App\Modules\Inscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController
{
    public function viewLegacyFile(Request $request)
    {
        $path = urldecode($request->query('path'));
        
        // Enlever le préfixe 'public/' si présent car on utilise le disque 'public'
        $path = str_starts_with($path, 'public/') ? substr($path, 7) : $path;
        
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $mimeType = Storage::disk('public')->mimeType($path);
        
        return response()->stream(function () use ($path) {
            $stream = Storage::disk('public')->readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
        ]);
    }
}

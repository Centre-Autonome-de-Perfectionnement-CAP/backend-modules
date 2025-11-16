<?php

namespace App\Modules\Inscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController
{
    public function viewLegacyFile(Request $request)
    {
        $path = $request->query('path');
        
        if (!$path || !Storage::exists($path)) {
            abort(404, 'File not found');
        }

        $mimeType = Storage::mimeType($path);
        
        return response()->stream(function () use ($path) {
            $stream = Storage::readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
        ]);
    }
}

<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stockage\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function viewDocument(File $file)
    {
        if (!$file->is_official_document) {
            abort(404);
        }

        if ($file->disk === 'external') {
            return redirect($file->file_path);
        }

        // Le file_path contient déjà le chemin complet
        $filePath = $file->file_path;
        
        // Si le chemin commence par 'public/', on l'enlève car le disk 'public' pointe déjà vers storage/app/public
        if (str_starts_with($filePath, 'public/')) {
            $filePath = substr($filePath, 7);
        }
        
        if (!Storage::disk($file->disk)->exists($filePath)) {
            abort(404, 'File not found at: ' . $filePath);
        }

        return response()->stream(function () use ($file, $filePath) {
            $stream = Storage::disk($file->disk)->readStream($filePath);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
        ]);
    }
}

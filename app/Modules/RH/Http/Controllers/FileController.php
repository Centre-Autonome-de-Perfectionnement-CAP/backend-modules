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
        if (!Storage::disk($file->disk)->exists($file->file_path)) {
            abort(404);
        }

        return response()->stream(function () use ($file) {
            $stream = Storage::disk($file->disk)->readStream($file->file_path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
        ]);
    }
}

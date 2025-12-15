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

        $path = 'files/' . $file->file_path;
        if (!Storage::disk($file->disk)->exists($path)) {
            abort(404);
        }

        return response()->stream(function () use ($file, $path) {
            $stream = Storage::disk($file->disk)->readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
        ]);
    }
}

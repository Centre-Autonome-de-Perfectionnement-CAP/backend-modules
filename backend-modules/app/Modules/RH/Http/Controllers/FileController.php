<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stockage\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function viewDocument($fileId)
    {
        \Log::info('FileController: Tentative d\'accès au fichier', ['file_id' => $fileId]);
        
        $file = File::find($fileId);
        
        if (!$file) {
            \Log::error('FileController: Fichier non trouvé', ['file_id' => $fileId]);
            abort(404, 'Fichier non trouvé avec l\'ID: ' . $fileId);
        }
        
        \Log::info('FileController: Fichier trouvé', [
            'file_id' => $fileId,
            'is_official_document' => $file->is_official_document,
            'disk' => $file->disk,
            'file_path' => $file->file_path
        ]);

        if (!$file->is_official_document) {
            \Log::error('FileController: Fichier pas un document officiel', ['file_id' => $fileId]);
            abort(404, 'Ce fichier n\'est pas un document officiel');
        }

        if ($file->disk === 'external') {
            \Log::info('FileController: Redirection vers lien externe', ['file_path' => $file->file_path]);
            return redirect($file->file_path);
        }

        // Le file_path contient déjà le chemin complet
        $filePath = $file->file_path;
        
        // Si le chemin commence par 'public/', on l'enlève car le disk 'public' pointe déjà vers storage/app/public
        if (str_starts_with($filePath, 'public/')) {
            $filePath = substr($filePath, 7);
        }
        
        // Ajouter le préfixe 'files/' si le fichier n'est pas trouvé directement
        if (!Storage::disk($file->disk)->exists($filePath)) {
            $filePathWithPrefix = 'files/' . $filePath;
            if (Storage::disk($file->disk)->exists($filePathWithPrefix)) {
                $filePath = $filePathWithPrefix;
            }
        }
        
        \Log::info('FileController: Vérification existence fichier physique', [
            'disk' => $file->disk,
            'file_path' => $filePath
        ]);
        
        if (!Storage::disk($file->disk)->exists($filePath)) {
            \Log::error('FileController: Fichier physique introuvable', [
                'disk' => $file->disk,
                'file_path' => $filePath
            ]);
            
            // Fallback : essayer d'utiliser le lien direct si disponible
            if ($file->file_path && filter_var($file->file_path, FILTER_VALIDATE_URL)) {
                \Log::info('FileController: Fallback vers lien direct', ['url' => $file->file_path]);
                return redirect($file->file_path);
            }
            
            abort(404, 'Fichier physique introuvable: ' . $filePath);
        }

        // Vérifier si c'est une demande de téléchargement
        $isDownload = request()->query('download') === '1';
        $disposition = $isDownload ? 'attachment' : 'inline';
        
        \Log::info('FileController: Téléchargement du fichier', [
            'file_id' => $fileId,
            'disposition' => $disposition,
            'filename' => $file->original_name
        ]);

        return response()->stream(function () use ($file, $filePath) {
            $stream = Storage::disk($file->disk)->readStream($filePath);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => $disposition . '; filename="' . $file->original_name . '"',
        ]);
    }
}

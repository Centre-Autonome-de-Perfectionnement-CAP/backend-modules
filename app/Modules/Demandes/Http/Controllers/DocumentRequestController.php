<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lecture : liste et détail d'une demande.
 */
class DocumentRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DocumentRequestQueryService $queryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user    = Auth::user();
        $role    = $user->roles->first()?->slug ?? null;
        $filters = $request->only(['status', 'type', 'search']);

        $demandes = $this->queryService->listing($role, $user, $filters);

        return response()->json([
            'success' => true,
            'data'    => $demandes,
            'role'    => $role,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $demande = $this->queryService->findOrFail($id);
            return $this->successResponse($demande);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    public function uploadSecretaryFiles(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if ($user->roles->first()?->slug !== 'secretaire') {
            return $this->errorResponse('Action non autorisée. Seule la secrétaire peut ajouter des fichiers.', 403);
        }

        try {
            $demande = \App\Modules\Demandes\Models\DocumentRequest::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Demande introuvable.');
        }

        if (!in_array($demande->status, ['submitted', 'secretary_correction'])) {
            return $this->errorResponse('Le statut actuel du dossier ne permet pas l\'ajout de fichiers.', 403);
        }

        $request->validate([
            'files'            => 'required|array|min:1',
            'files.*.file'     => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'files.*.name'     => 'required|string|max:200',
            'files.*.comment'  => 'nullable|string|max:1000',
        ]);

        $uploadedFiles = $request->file('files') ?? [];
        if (empty($uploadedFiles)) {
            return $this->errorResponse('Aucun fichier reçu.', 422);
        }

        $secretaryFiles = $demande->secretary_files ?? [];
        $baseFolder = "attestation-demandes/{$demande->reference}/secretary";

        $newFiles = [];

        foreach ($request->input('files', []) as $index => $fileData) {
            $file = $request->file("files.{$index}.file");
            if (!$file || !$file->isValid()) continue;

            $ext = $file->getClientOriginalExtension() ?: $file->extension();
            $fileId = uniqid('sec_');
            $fileName = $fileId . '.' . $ext;
            
            $path = $file->storeAs($baseFolder, $fileName, 'public');

            $newFile = [
                'id'            => $fileId,
                'path'          => $path,
                'original_name' => strip_tags($fileData['name']),
                'comment'       => isset($fileData['comment']) ? strip_tags($fileData['comment']) : null,
                'uploaded_at'   => now()->toIso8601String(),
            ];
            
            $secretaryFiles[] = $newFile;
            $newFiles[] = $newFile;
        }

        $demande->update(['secretary_files' => $secretaryFiles]);

        return $this->successResponse([
            'message' => 'Fichiers ajoutés avec succès.',
            'secretary_files' => $secretaryFiles,
            'new_files' => $newFiles
        ]);
    }

    public function updateSecretaryFileComment(Request $request, int $id, string $fileId): JsonResponse
    {
        $user = Auth::user();
        if ($user->roles->first()?->slug !== 'secretaire') {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        $demande = \App\Modules\Demandes\Models\DocumentRequest::findOrFail($id);
        
        if (!in_array($demande->status, ['submitted', 'secretary_correction'])) {
            return $this->errorResponse('Le statut actuel du dossier ne permet pas de modifier un commentaire.', 403);
        }

        $request->validate([
            'comment' => 'nullable|string|max:1000'
        ]);

        $secretaryFiles = $demande->secretary_files ?? [];
        $found = false;

        foreach ($secretaryFiles as &$file) {
            if (($file['id'] ?? '') === $fileId) {
                $file['comment'] = strip_tags($request->input('comment'));
                $found = true;
                break;
            }
        }

        if (!$found) {
            return $this->errorResponse('Fichier introuvable.', 404);
        }

        $demande->update(['secretary_files' => $secretaryFiles]);

        return $this->successResponse([
            'message' => 'Commentaire mis à jour.',
            'secretary_files' => $secretaryFiles
        ]);
    }

    public function deleteSecretaryFile(int $id, string $fileId): JsonResponse
    {
        $user = Auth::user();
        if ($user->roles->first()?->slug !== 'secretaire') {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        $demande = \App\Modules\Demandes\Models\DocumentRequest::findOrFail($id);
        
        if (!in_array($demande->status, ['submitted', 'secretary_correction'])) {
            return $this->errorResponse('Le statut actuel du dossier ne permet pas de supprimer un fichier.', 403);
        }

        $secretaryFiles = $demande->secretary_files ?? [];
        $newFiles = [];
        $fileToDelete = null;

        foreach ($secretaryFiles as $file) {
            if (($file['id'] ?? '') === $fileId) {
                $fileToDelete = $file;
            } else {
                $newFiles[] = $file;
            }
        }

        if (!$fileToDelete) {
            return $this->errorResponse('Fichier introuvable.', 404);
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($fileToDelete['path'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($fileToDelete['path']);
        }

        $demande->update(['secretary_files' => $newFiles]);

        return $this->successResponse([
            'message' => 'Fichier supprimé.',
            'secretary_files' => $newFiles
        ]);
    }
}

<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Models\DocumentRequest;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use App\Modules\Demandes\Services\DocumentStorageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Storage};

/**
 * Lecture : liste, détail et gestion des pièces jointes d'une demande.
 *
 * Endpoint fichiers :
 *   GET  /api/attestations/document-requests/{id}/files/{source}/{key}
 *     source = initial | complement | active | secretary
 *     - initial    : fichier dans demande/
 *     - complement : fichier dans complement/
 *     - active     : version prioritaire (complement > demande)  ← NOUVEAU
 *     - secretary  : fichier ajouté par la secrétaire
 *
 * Fichiers secrétaire :
 *   POST   /api/attestations/document-requests/{id}/secretary-files
 *   PATCH  /api/attestations/document-requests/{id}/secretary-files/{fileId}
 *   DELETE /api/attestations/document-requests/{id}/secretary-files/{fileId}
 */
class DocumentRequestController extends Controller
{
    use ApiResponse;

    private const FILE_LABELS = [
        'demande_manuscrite'       => 'Demande manuscrite',
        'acte_naissance'           => 'Acte de naissance',
        'attestation_succes_file'  => 'Attestation de succes',
        'quittance'                => 'Quittance',
        'quittance_online'         => 'Quittance en ligne',
        'recu_paiement'            => 'Recu de paiement',
        'bulletin'                 => 'Bulletin de notes',
    ];

    public function __construct(
        protected DocumentRequestQueryService $queryService,
        protected DocumentStorageService      $storageService,
    ) {}

    // ── Listing ───────────────────────────────────────────────────────────────

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

    // ── Détail ────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        try {
            $demande = $this->queryService->findOrFail($id);

            // Enrichir la réponse avec la résolution de priorité des documents
            $resolved = $this->storageService->resolveDocuments(
                $demande->files,
                $demande->complement_files
            );

            $result          = (array) $demande;
            $result['documents_resolus'] = $resolved;

            return $this->successResponse((object) $result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    // ── Aperçu / téléchargement d'une pièce jointe ───────────────────────────

    /**
     * GET /api/attestations/document-requests/{id}/files/{source}/{key}
     *
     * source :
     *   - initial    → lit dans files (demande/)
     *   - complement → lit dans complement_files (complement/)
     *   - active     → lit la version prioritaire via resolveActiveFile()
     *   - secretary  → lit dans secretary_files (secretaire/)
     *
     * ?download=1 → Content-Disposition: attachment (défaut : inline)
     */
    public function previewFile(Request $request, int $id, string $source, string $key)
    {
        try {
            $demande = $this->queryService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }

        $path        = null;
        $displayName = null;

        if ($source === 'initial') {
            $decoded = $this->decodeJson($demande->files);
            if (!array_key_exists($key, $decoded)) {
                return $this->notFoundResponse('Fichier introuvable.');
            }
            $path        = $decoded[$key];
            $displayName = $this->buildDisplayName($key, $path);

        } elseif ($source === 'complement') {
            $decoded = $this->decodeJson($demande->complement_files);
            if (!array_key_exists($key, $decoded)) {
                return $this->notFoundResponse('Fichier introuvable.');
            }
            $path        = $decoded[$key];
            $displayName = $this->buildDisplayName($key, $path);

        } elseif ($source === 'active') {
            // Version prioritaire : complement > demande
            $resolved = $this->storageService->resolveActiveFile(
                $demande->files,
                $demande->complement_files,
                $key
            );
            if (!$resolved['path']) {
                return $this->notFoundResponse('Fichier introuvable.');
            }
            $path        = $resolved['path'];
            $displayName = $this->buildDisplayName($key, $path);

        } elseif ($source === 'secretary') {
            $decoded = $this->decodeJson($demande->secretary_files);
            $found   = null;
            foreach ($decoded as $file) {
                if (($file['id'] ?? '') === $key) {
                    $found = $file;
                    break;
                }
            }
            if (!$found) {
                return $this->notFoundResponse('Fichier introuvable.');
            }
            $path        = $found['path'] ?? null;
            $displayName = $found['original_name'] ?? basename((string) $path);

        } else {
            return $this->errorResponse('Source de fichier invalide. Valeurs : initial, complement, active, secretary.', 422);
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return $this->notFoundResponse('Fichier introuvable sur le serveur.');
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return Storage::disk('public')->response($path, $displayName, [], $disposition);
    }

    // ── Fichiers secrétaire ───────────────────────────────────────────────────

    public function uploadSecretaryFiles(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if ($user->roles->first()?->slug !== 'secretaire') {
            return $this->errorResponse('Action non autorisée. Seule la secrétaire peut ajouter des fichiers.', 403);
        }

        try {
            $demande = DocumentRequest::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Demande introuvable.');
        }

        if (!in_array($demande->status, ['submitted', 'secretary_correction'])) {
            return $this->errorResponse("Le statut actuel du dossier ne permet pas l'ajout de fichiers.", 403);
        }

        $request->validate([
            'files'           => 'required|array|min:1',
            'files.*.file'    => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'files.*.name'    => 'required|string|max:200',
            'files.*.comment' => 'nullable|string|max:1000',
        ]);

        $secretaryFiles = $demande->secretary_files ?? [];
        $newFiles       = [];

        foreach ($request->input('files', []) as $index => $fileData) {
            $file = $request->file("files.{$index}.file");
            if (!$file || !$file->isValid()) continue;

            $fileId = uniqid('sec_');

            // Stockage dans secretaire/ via le service
            $path = $this->storageService->storeSecretaireFile(
                $demande->type,
                $demande->reference,
                $file,
                $fileId
            );

            $newFile = [
                'id'            => $fileId,
                'path'          => $path,
                'original_name' => strip_tags($fileData['name']),
                'comment'       => isset($fileData['comment']) ? strip_tags($fileData['comment']) : null,
                'uploaded_at'   => now()->toIso8601String(),
            ];

            $secretaryFiles[] = $newFile;
            $newFiles[]       = $newFile;
        }

        $demande->update(['secretary_files' => $secretaryFiles]);

        return $this->successResponse([
            'message'         => 'Fichiers ajoutés avec succès.',
            'secretary_files' => $secretaryFiles,
            'new_files'       => $newFiles,
        ]);
    }

    public function updateSecretaryFileComment(Request $request, int $id, string $fileId): JsonResponse
    {
        $user = Auth::user();
        if ($user->roles->first()?->slug !== 'secretaire') {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        $demande = DocumentRequest::findOrFail($id);

        if (!in_array($demande->status, ['submitted', 'secretary_correction'])) {
            return $this->errorResponse('Le statut actuel du dossier ne permet pas de modifier un commentaire.', 403);
        }

        $request->validate(['comment' => 'nullable|string|max:1000']);

        $secretaryFiles = $demande->secretary_files ?? [];
        $found          = false;

        foreach ($secretaryFiles as &$file) {
            if (($file['id'] ?? '') === $fileId) {
                $file['comment'] = strip_tags($request->input('comment'));
                $found           = true;
                break;
            }
        }

        if (!$found) {
            return $this->errorResponse('Fichier introuvable.', 404);
        }

        $demande->update(['secretary_files' => $secretaryFiles]);

        return $this->successResponse([
            'message'         => 'Commentaire mis à jour.',
            'secretary_files' => $secretaryFiles,
        ]);
    }

    public function deleteSecretaryFile(int $id, string $fileId): JsonResponse
    {
        $user = Auth::user();
        if ($user->roles->first()?->slug !== 'secretaire') {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        $demande = DocumentRequest::findOrFail($id);

        if (!in_array($demande->status, ['submitted', 'secretary_correction'])) {
            return $this->errorResponse("Le statut actuel du dossier ne permet pas de supprimer un fichier.", 403);
        }

        $secretaryFiles = $demande->secretary_files ?? [];
        $newFiles       = [];
        $fileToDelete   = null;

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

        if (!empty($fileToDelete['path'])) {
            $this->storageService->deleteFile($fileToDelete['path']);
        }

        $demande->update(['secretary_files' => $newFiles]);

        return $this->successResponse([
            'message'         => 'Fichier supprimé.',
            'secretary_files' => $newFiles,
        ]);
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    private function decodeJson(mixed $raw): array
    {
        if (is_array($raw))  return $raw;
        if (is_string($raw)) return json_decode($raw, true) ?: [];
        return [];
    }

    private function buildDisplayName(string $key, ?string $path): string
    {
        $ext  = $path ? (pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf') : 'pdf';
        return (self::FILE_LABELS[$key] ?? $key) . '.' . $ext;
    }
}

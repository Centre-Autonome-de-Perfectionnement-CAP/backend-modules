<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\Models\DocumentRequest;
use App\Exceptions\BusinessException;
use Illuminate\Http\Request;

/**
 * CORRECTIF (v2) — Service extrait du DocumentRequestController réel
 *
 * Logique reprise à l'identique (statuts autorisés, génération d'ID,
 * strip_tags sur les champs texte, structure du tableau secretary_files).
 * Seule différence avec le contrôleur original : la vérification de rôle
 * ('secretaire' uniquement) est extraite vers DocumentRequestPolicy (B1.3),
 * appelée par le contrôleur via $this->authorize() — ce service ne fait
 * plus que la vérification de STATUT (qui est une règle métier, pas une
 * règle d'autorisation).
 */
class SecretaryFileService
{
    private const ALLOWED_STATUSES = ['submitted', 'secretary_correction'];

    public function __construct(
        private readonly DocumentStorageService $storageService,
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // UPLOAD
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    public function upload(DocumentRequest $demande, array $filesInput, Request $request): array
    {
        $this->assertStatusAllowed($demande, "l'ajout de fichiers");

        $secretaryFiles = $demande->secretary_files ?? [];
        $newFiles       = [];

        foreach ($filesInput as $index => $fileData) {
            $file = $request->file("files.{$index}.file");
            if (!$file || !$file->isValid()) continue;

            $fileId = uniqid('sec_');

            $path = $this->storageService->storeSecretaireFile(
                $demande->type, $demande->reference, $file, $fileId
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

        return [
            'secretary_files' => $secretaryFiles,
            'new_files'       => $newFiles,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // UPDATE COMMENTAIRE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    public function updateComment(DocumentRequest $demande, string $fileId, ?string $comment): array
    {
        $this->assertStatusAllowed($demande, 'la modification de commentaire');

        $secretaryFiles = $demande->secretary_files ?? [];
        $found          = false;

        foreach ($secretaryFiles as &$file) {
            if (($file['id'] ?? '') === $fileId) {
                $file['comment'] = strip_tags($comment ?? '');
                $found           = true;
                break;
            }
        }
        unset($file);

        if (!$found) {
            throw new BusinessException('Fichier introuvable.', 'FILE_NOT_FOUND', 404);
        }

        $demande->update(['secretary_files' => $secretaryFiles]);

        return $secretaryFiles;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SUPPRESSION
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    public function delete(DocumentRequest $demande, string $fileId): array
    {
        $this->assertStatusAllowed($demande, 'la suppression de fichier');

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
            throw new BusinessException('Fichier introuvable.', 'FILE_NOT_FOUND', 404);
        }

        if (!empty($fileToDelete['path'])) {
            $this->storageService->deleteFile($fileToDelete['path']);
        }

        $demande->update(['secretary_files' => $newFiles]);

        return $newFiles;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // REMPLACEMENT DE FICHIER
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Remplace le fichier physique d'une entrée secrétaire existante.
     * Le commentaire est conservé (ou mis à jour si fourni).
     *
     * @throws BusinessException
     */
    public function replaceFile(DocumentRequest $demande, string $fileId, Request $request): array
    {
        $this->assertStatusAllowed($demande, 'le remplacement de fichier');

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            throw new BusinessException('Aucun fichier valide fourni.', 'NO_FILE', 422);
        }

        $secretaryFiles = $demande->secretary_files ?? [];
        $found          = false;

        foreach ($secretaryFiles as &$entry) {
            if (($entry['id'] ?? '') === $fileId) {
                // Supprimer l'ancien fichier physique si présent
                if (!empty($entry['path'])) {
                    $this->storageService->deleteFile($entry['path']);
                }

                // Stocker le nouveau fichier avec le même ID pour conserver la continuité
                $newPath = $this->storageService->storeSecretaireFile(
                    $demande->type, $demande->reference, $file, $fileId
                );

                $entry['path']          = $newPath;
                $entry['original_name'] = strip_tags(
                    $request->input('name') ?: $file->getClientOriginalName()
                );
                if ($request->has('comment')) {
                    $entry['comment'] = strip_tags($request->input('comment') ?? '');
                }
                $entry['replaced_at'] = now()->toIso8601String();
                $found = true;
                break;
            }
        }
        unset($entry);

        if (!$found) {
            throw new BusinessException('Fichier introuvable.', 'FILE_NOT_FOUND', 404);
        }

        $demande->update(['secretary_files' => $secretaryFiles]);

        return $secretaryFiles;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPER
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    private function assertStatusAllowed(DocumentRequest $demande, string $actionLabel): void
    {
        if (!in_array($demande->status, self::ALLOWED_STATUSES)) {
            throw new BusinessException("Le statut actuel du dossier ne permet pas {$actionLabel}.", 'STATUS_NOT_ALLOWED', 403);
        }
    }
}

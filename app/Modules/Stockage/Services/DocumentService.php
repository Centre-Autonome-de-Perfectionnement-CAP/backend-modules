<?php

namespace App\Modules\Stockage\Services;

use App\Modules\Stockage\Models\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class DocumentService
{
    public function __construct(
        protected FileStorageService $fileStorageService
    ) {}

    /**
     * Récupérer tous les documents officiels
     */
    public function getAll(array $filters = []): \Illuminate\Support\Collection
    {
        $query = File::officialDocuments()
            ->orderBy('date_publication', 'desc');

        if (!empty($filters['categorie'])) {
            $query->byCategorie($filters['categorie']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(function ($file) {
            $typeMap = [
                'pdf' => 'pdf',
                'doc' => 'doc', 'docx' => 'doc',
                'xls' => 'xls', 'xlsx' => 'xls',
                'ppt' => 'ppt', 'pptx' => 'ppt',
            ];
            $type = $typeMap[$file->extension] ?? 'lien';

            return [
                'id' => $file->id,
                'titre' => $file->original_name,
                'description' => $file->description ?? '',
                'type' => $type,
                'taille' => $file->size_for_humans,
                'datePublication' => $file->date_publication?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'lien' => $file->url,
                'categorie' => $file->document_categorie,
            ];
        });
    }

    /**
     * Uploader un document officiel
     */
    public function upload(array $data, $file, int $userId): File
    {
        return DB::transaction(function () use ($data, $file, $userId) {
            // Upload via FileStorageService
            $uploadedFile = $this->fileStorageService->uploadFile(
                uploadedFile: $file,
                userId: $userId,
                visibility: $data['visibility'] ?? 'public',
                collection: 'official_documents',
                moduleName: 'Stockage',
                moduleResourceType: 'Document',
                metadata: [
                    'categorie' => $data['categorie'] ?? 'administratif',
                    'is_official_document' => true,
                ]
            );

            // Mettre à jour avec les infos supplémentaires
            $uploadedFile->update([
                'description' => $data['description'] ?? null,
                'document_categorie' => $data['categorie'] ?? 'administratif',
                'date_publication' => $data['date_publication'] ?? now(),
                'is_official_document' => true,
            ]);

            Log::info('Document officiel uploadé', [
                'file_id' => $uploadedFile->id,
                'name' => $uploadedFile->original_name,
                'categorie' => $data['categorie'] ?? 'administratif',
            ]);

            return $uploadedFile;
        });
    }

    /**
     * Récupérer un document par ID
     */
    public function getById(int $id): ?File
    {
        return File::find($id);
    }

    /**
     * Mettre à jour un document
     */
    public function update(File $document, array $data): File
    {
        return DB::transaction(function () use ($document, $data) {
            $document->update([
                'description' => $data['description'] ?? $document->description,
                'document_categorie' => $data['categorie'] ?? $document->document_categorie,
                'date_publication' => $data['date_publication'] ?? $document->date_publication,
            ]);

            Log::info('Document mis à jour', [
                'file_id' => $document->id,
            ]);

            return $document->fresh();
        });
    }

    /**
     * Supprimer un document
     */
    public function delete(File $document, int $userId): bool
    {
        try {
            $this->fileStorageService->forceDeleteFile($document, $userId);

            Log::info('Document supprimé', [
                'file_id' => $document->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur suppression document', [
                'file_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Télécharger un document
     */
    public function download(File $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::disk($document->disk)->exists($document->path)) {
            throw new Exception('Fichier introuvable sur le disque');
        }

        Log::info('Document téléchargé', [
            'file_id' => $document->id,
            'name' => $document->original_name,
        ]);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name
        );
    }

    /**
     * Récupérer les documents par catégorie
     */
    public function getByCategorie(string $categorie): \Illuminate\Support\Collection
    {
        return $this->getAll(['categorie' => $categorie]);
    }

    /**
     * Publier un document (rendre visible)
     */
    public function publish(File $document): File
    {
        $document->update([
            'date_publication' => now(),
            'visibility' => 'public',
        ]);

        Log::info('Document publié', [
            'file_id' => $document->id,
        ]);

        return $document->fresh();
    }

    /**
     * Dépublier un document (rendre privé)
     */
    public function unpublish(File $document): File
    {
        $document->update([
            'visibility' => 'private',
        ]);

        Log::info('Document dépublié', [
            'file_id' => $document->id,
        ]);

        return $document->fresh();
    }

    /**
     * Récupérer les statistiques des documents
     */
    public function getStatistics(): array
    {
        return [
            'total' => File::officialDocuments()->count(),
            'administratif' => File::officialDocuments()->byCategorie('administratif')->count(),
            'pedagogique' => File::officialDocuments()->byCategorie('pedagogique')->count(),
            'legal' => File::officialDocuments()->byCategorie('legal')->count(),
            'organisation' => File::officialDocuments()->byCategorie('organisation')->count(),
            'total_size' => File::officialDocuments()->sum('size'),
        ];
    }
}

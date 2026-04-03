<?php

namespace App\Modules\RH\Services;

use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Services\FileStorageService;
use Illuminate\Support\Facades\DB;

class DocumentManagementService
{
    public function __construct(
        protected FileStorageService $fileStorageService
    ) {}

    public function getAll(?string $categorie = null)
    {
        $query = File::officialDocuments()->orderBy('date_publication', 'desc');

        if ($categorie) {
            $query->byCategorie($categorie);
        }

        return $query->get()->map(fn($file) => $this->formatDocument($file));
    }

    public function create(array $data, $uploadedFile, int $userId): File
    {
        return DB::transaction(function () use ($data, $uploadedFile, $userId) {
            if ($uploadedFile) {
                $file = $this->fileStorageService->uploadFile(
                    uploadedFile: $uploadedFile,
                    userId: $userId,
                    visibility: 'public',
                    collection: 'official_documents',
                    moduleName: 'Stockage',
                    moduleResourceType: 'Document'
                );

                $file->update([
                    'original_name' => $data['titre'],
                    'description' => $data['description'],
                    'document_categorie' => $data['categorie'],
                    'date_publication' => $data['datePublication'],
                    'is_official_document' => true,
                    'disk' => 'public',
                ]);

                return $file;
            }

            return File::create([
                'user_id' => $userId,
                'stored_name' => uniqid('link_'),
                'original_name' => $data['titre'],
                'description' => $data['description'],
                'file_path' => $data['lien'],
                'disk' => 'external',
                'visibility' => 'public',
                'mime_type' => 'text/html',
                'extension' => 'url',
                'size' => 0,
                'is_official_document' => true,
                'document_categorie' => $data['categorie'],
                'date_publication' => $data['datePublication'],
            ]);
        });
    }

    public function update(File $file, array $data): File
    {
        $file->update([
            'original_name' => $data['titre'] ?? $file->original_name,
            'description' => $data['description'] ?? $file->description,
            'date_publication' => $data['datePublication'] ?? $file->date_publication,
            'document_categorie' => $data['categorie'] ?? $file->document_categorie,
        ]);

        return $file->fresh();
    }

    public function delete(File $file, int $userId): void
    {
        $this->fileStorageService->forceDeleteFile($file, $userId);
    }

    public function formatDocument(File $file): array
    {
        $url = $file->disk === 'external' 
            ? $file->file_path 
            : url('/api/rh/files/' . $file->id);

        return [
            'id' => $file->id,
            'titre' => $file->original_name,
            'description' => $file->description,
            'type' => $this->getFileType($file->extension),
            'taille' => $file->size_for_humans,
            'datePublication' => $file->date_publication?->format('Y-m-d'),
            'lien' => $url,
            'categorie' => $file->document_categorie,
            'created_at' => $file->created_at,
        ];
    }

    private function getFileType(string $extension): string
    {
        $typeMap = [
            'pdf' => 'pdf',
            'doc' => 'doc', 'docx' => 'doc',
            'xls' => 'xls', 'xlsx' => 'xls',
            'ppt' => 'ppt', 'pptx' => 'ppt',
        ];
        return $typeMap[$extension] ?? 'lien';
    }
}

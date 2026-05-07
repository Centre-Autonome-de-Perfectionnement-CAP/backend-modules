<?php

namespace App\Modules\Cours\Services;

use App\Modules\Cours\Models\CourseElementResource;
use App\Modules\Stockage\Services\FileStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CourseElementResourceService
{
    public function __construct(
        protected FileStorageService $fileStorageService
    ) {}

    /**
     * Récupérer toutes les ressources avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = CourseElementResource::query()->with(['courseElement', 'file']);

        if (!empty($filters['course_element_id'])) {
            $query->where('course_element_id', $filters['course_element_id']);
        }

        if (!empty($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer une nouvelle ressource
     */
    public function create(array $data, $uploadedFile, int $userId): CourseElementResource
    {
        return DB::transaction(function () use ($data, $uploadedFile, $userId) {
            // Upload du fichier via le service de stockage
            $mime = $uploadedFile->getClientOriginalExtension();
            $resourceType = $this->getResourceTypeFromExtension($mime);

            $file = $this->fileStorageService->uploadFile(
                uploadedFile: $uploadedFile,
                userId: $userId,
                visibility: $data['is_public'] ?? false ? 'public' : 'private',
                collection: 'course_resources',
                moduleName: 'Cours',
                moduleResourceType: 'CourseElementResource',
                metadata: [
                    'course_element_id' => $data['course_element_id'],
                    'pedagogical_type' => $data['pedagogical_type'], // nouveau champ
                    'resource_type' => $resourceType,
                ]
            );

            // Créer la ressource
            $resource = CourseElementResource::create([
                'course_element_id' => $data['course_element_id'],
                'file_id' => $file->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'resource_type' => $data['pedagogical_type'],
                'is_public' => $data['is_public'] ?? false,
            ]);

            // Mettre à jour la relation du fichier
            $file->update(['module_resource_id' => $resource->id]);

            Log::info('Ressource pédagogique créée', [
                'resource_id' => $resource->id,
                'course_element_id' => $resource->course_element_id,
                'file_id' => $file->id,
            ]);

            return $resource;
        });
    }

    /**
     * Récupérer une ressource par ID
     */
    public function getById(int $id): ?CourseElementResource
    {
        return CourseElementResource::with(['courseElement', 'file'])->find($id);
    }

    /**
     * Mettre à jour une ressource
     */
    public function update(CourseElementResource $resource, array $data): CourseElementResource
    {
        $resource->update($data);

        Log::info('Ressource pédagogique mise à jour', [
            'resource_id' => $resource->id,
        ]);

        return $resource->fresh(['file']);
    }

    /**
     * Supprimer une ressource
     */
    public function delete(CourseElementResource $resource, int $userId): bool
    {
        return DB::transaction(function () use ($resource, $userId) {
            try {
                // Supprimer le fichier associé
                if ($resource->file) {
                    $this->fileStorageService->forceDeleteFile($resource->file, $userId);
                }

                $resource->delete();

                Log::info('Ressource pédagogique supprimée', [
                    'resource_id' => $resource->id,
                ]);

                return true;
            } catch (Exception $e) {
                Log::error('Erreur lors de la suppression de la ressource', [
                    'resource_id' => $resource->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    protected function getResourceTypeFromExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'pdf',
            'ppt', 'pptx' => 'pptx',
            'doc', 'docx' => 'docx',
            'mp4' => 'video',
            'mp3' => 'audio',
            default => 'other',
        };
    }
}

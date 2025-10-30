<?php

namespace App\Modules\Stockage\Services;

use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Models\FileActivity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FileStorageService
{
    /**
     * Upload un fichier et crée l'enregistrement.
     *
     * @param UploadedFile $uploadedFile Le fichier à uploader
     * @param int $userId ID de l'utilisateur
     * @param string $visibility Visibilité (public/private)
     * @param string $collection Collection/catégorie
     * @param string|null $moduleName Nom du module propriétaire
     * @param string|null $moduleResourceType Type de ressource du module
     * @param int|null $moduleResourceId ID de la ressource du module
     * @param array $metadata Métadonnées additionnelles
     * @return File
     * @throws \Exception
     */
    public function uploadFile(
        UploadedFile $uploadedFile,
        int $userId,
        string $visibility = 'private',
        string $collection = 'default',
        ?string $moduleName = null,
        ?string $moduleResourceType = null,
        ?int $moduleResourceId = null,
        array $metadata = []
    ): File {
        return DB::transaction(function () use (
            $uploadedFile,
            $userId,
            $visibility,
            $collection,
            $moduleName,
            $moduleResourceType,
            $moduleResourceId,
            $metadata
        ) {
            // Déterminer le disk selon la visibilité
            $disk = $visibility === 'public' ? 'public_files' : 'private_files';

            // Générer un nom unique
            $extension = $uploadedFile->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;

            // Construire le chemin
            $path = $this->buildPath($collection, $moduleName, $filename);

            // Stocker le fichier
            $uploadedFile->storeAs(dirname($path), basename($path), $disk);

            // Calculer le hash
            $fileHash = hash_file('sha256', $uploadedFile->getRealPath());

            // Créer l'enregistrement (name et path sont des colonnes générées)
            $file = File::create([
                'user_id' => $userId,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'stored_name' => $filename,
                'file_path' => $path,
                'disk' => $disk,
                'visibility' => $visibility,
                'module_name' => $moduleName,
                'module_resource_type' => $moduleResourceType,
                'module_resource_id' => $moduleResourceId,
                'collection' => $collection,
                'size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType(),
                'extension' => $extension,
                'file_hash' => $fileHash,
                'metadata' => $metadata,
            ]);

            // Logger l'activité
            FileActivity::log(
                $file->id,
                $userId,
                'uploaded',
                "Fichier '{$file->original_name}' uploadé"
            );

            return $file;
        });
    }

    /**
     * Télécharge un fichier.
     *
     * @param File $file
     * @param int|null $userId
     * @return array
     */
    public function downloadFile(File $file, ?int $userId = null): array
    {
        if (!$file->exists()) {
            throw new \App\Exceptions\ResourceNotFoundException('Fichier sur le disque');
        }

        // Incrémenter le compteur
        $file->incrementDownloadCount();

        // Logger l'activité
        FileActivity::log(
            $file->id,
            $userId,
            'downloaded',
            "Fichier '{$file->original_name}' téléchargé"
        );

        return [
            'file' => $file,
            'stream' => Storage::disk($file->disk)->get($file->path),
            'mimeType' => $file->mime_type,
            'filename' => $file->original_name,
        ];
    }

    /**
     * Supprime un fichier (soft delete).
     *
     * @param File $file
     * @param int|null $userId
     * @return bool
     */
    public function deleteFile(File $file, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($file, $userId) {
            // Logger l'activité avant suppression
            FileActivity::log(
                $file->id,
                $userId,
                'deleted',
                "Fichier '{$file->original_name}' supprimé"
            );

            // Soft delete
            return $file->delete();
        });
    }

    /**
     * Supprime définitivement un fichier.
     *
     * @param File $file
     * @param int|null $userId
     * @return bool
     */
    public function forceDeleteFile(File $file, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($file, $userId) {
            // Supprimer du disque
            if ($file->exists()) {
                Storage::disk($file->disk)->delete($file->path);
            }

            // Logger l'activité
            FileActivity::log(
                $file->id,
                $userId,
                'deleted',
                "Fichier '{$file->original_name}' supprimé définitivement"
            );

            // Suppression définitive
            return $file->forceDelete();
        });
    }

    /**
     * Déplace un fichier vers une autre collection.
     *
     * @param File $file
     * @param string $newCollection
     * @param int|null $userId
     * @return File
     */
    public function moveToCollection(File $file, string $newCollection, ?int $userId = null): File
    {
        return DB::transaction(function () use ($file, $newCollection, $userId) {
            $oldCollection = $file->collection;

            // Construire le nouveau chemin
            $newPath = $this->buildPath($newCollection, $file->module_name, $file->name);

            // Déplacer le fichier
            Storage::disk($file->disk)->move($file->path, $newPath);

            // Mettre à jour l'enregistrement
            $file->update([
                'collection' => $newCollection,
                'path' => $newPath,
            ]);

            // Logger l'activité
            FileActivity::log(
                $file->id,
                $userId,
                'moved',
                "Fichier déplacé de '{$oldCollection}' vers '{$newCollection}'"
            );

            return $file->fresh();
        });
    }

    /**
     * Change la visibilité d'un fichier.
     *
     * @param File $file
     * @param string $visibility
     * @param int|null $userId
     * @return File
     */
    public function changeVisibility(File $file, string $visibility, ?int $userId = null): File
    {
        return DB::transaction(function () use ($file, $visibility, $userId) {
            $oldVisibility = $file->visibility;
            $newDisk = $visibility === 'public' ? 'public_files' : 'private_files';

            // Si changement de disk nécessaire
            if ($file->disk !== $newDisk) {
                // Copier vers le nouveau disk
                $content = Storage::disk($file->disk)->get($file->path);
                Storage::disk($newDisk)->put($file->path, $content);

                // Supprimer de l'ancien disk
                Storage::disk($file->disk)->delete($file->path);

                // Mettre à jour le disk
                $file->disk = $newDisk;
            }

            // Mettre à jour la visibilité
            $file->update(['visibility' => $visibility, 'disk' => $newDisk]);

            // Logger l'activité
            FileActivity::log(
                $file->id,
                $userId,
                'updated',
                "Visibilité changée de '{$oldVisibility}' à '{$visibility}'"
            );

            return $file->fresh();
        });
    }

    /**
     * Verrouille un fichier.
     *
     * @param File $file
     * @param int $userId
     * @return File
     */
    public function lockFile(File $file, int $userId): File
    {
        $file->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $userId,
        ]);

        FileActivity::log(
            $file->id,
            $userId,
            'locked',
            "Fichier verrouillé"
        );

        return $file->fresh();
    }

    /**
     * Déverrouille un fichier.
     *
     * @param File $file
     * @param int $userId
     * @return File
     */
    public function unlockFile(File $file, int $userId): File
    {
        $file->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        FileActivity::log(
            $file->id,
            $userId,
            'unlocked',
            "Fichier déverrouillé"
        );

        return $file->fresh();
    }

    /**
     * Construit le chemin de stockage.
     *
     * @param string $collection
     * @param string|null $moduleName
     * @param string $filename
     * @return string
     */
    protected function buildPath(string $collection, ?string $moduleName, string $filename): string
    {
        $parts = [];

        if ($moduleName) {
            $parts[] = Str::slug($moduleName);
        }

        $parts[] = Str::slug($collection);
        $parts[] = date('Y/m');
        $parts[] = $filename;

        return implode('/', $parts);
    }

    /**
     * Récupère les fichiers d'un utilisateur.
     *
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserFiles(int $userId, array $filters = [])
    {
        $query = File::where('user_id', $userId);

        if (isset($filters['collection'])) {
            $query->where('collection', $filters['collection']);
        }

        if (isset($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (isset($filters['module_name'])) {
            $query->where('module_name', $filters['module_name']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Récupère les fichiers publics.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPublicFiles(array $filters = [])
    {
        $query = File::public();

        if (isset($filters['collection'])) {
            $query->where('collection', $filters['collection']);
        }

        if (isset($filters['module_name'])) {
            $query->where('module_name', $filters['module_name']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}

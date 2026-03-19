<?php

namespace App\Modules\Stockage\Traits;

use App\Modules\Stockage\Models\File;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait HasFiles
 * 
 * À utiliser dans le modèle User pour faciliter l'accès aux fichiers
 */
trait HasFiles
{
    /**
     * Les fichiers possédés par l'utilisateur.
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'user_id');
    }

    /**
     * Récupère les fichiers d'une collection spécifique.
     */
    public function filesInCollection(string $collection)
    {
        return $this->files()->where('collection', $collection)->get();
    }

    /**
     * Récupère les fichiers publics de l'utilisateur.
     */
    public function publicFiles()
    {
        return $this->files()->where('visibility', 'public')->get();
    }

    /**
     * Récupère les fichiers privés de l'utilisateur.
     */
    public function privateFiles()
    {
        return $this->files()->where('visibility', 'private')->get();
    }

    /**
     * Récupère les fichiers d'un module spécifique.
     */
    public function filesForModule(string $moduleName, ?string $resourceType = null, ?int $resourceId = null)
    {
        $query = $this->files()->where('module_name', $moduleName);
        
        if ($resourceType !== null) {
            $query->where('module_resource_type', $resourceType);
        }
        
        if ($resourceId !== null) {
            $query->where('module_resource_id', $resourceId);
        }
        
        return $query->get();
    }

    /**
     * Vérifie si l'utilisateur possède un fichier.
     */
    public function ownsFile(File $file): bool
    {
        return $file->user_id === $this->id;
    }
}

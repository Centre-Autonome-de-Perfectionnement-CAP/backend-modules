<?php

namespace App\Modules\Stockage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasUuid;

/**
 * @OA\Schema(
 *     schema="File",
 *     title="File",
 *     description="Modèle représentant un fichier stocké",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="user_id", type="integer", description="ID du propriétaire"),
 *     @OA\Property(property="name", type="string", description="Nom du fichier"),
 *     @OA\Property(property="original_name", type="string", description="Nom original du fichier"),
 *     @OA\Property(property="path", type="string", description="Chemin du fichier"),
 *     @OA\Property(property="disk", type="string", description="Disque de stockage"),
 *     @OA\Property(property="visibility", type="string", enum={"public", "private"}, description="Visibilité du fichier"),
 *     @OA\Property(property="module_name", type="string", description="Nom du module"),
 *     @OA\Property(property="module_resource_type", type="string", description="Type de ressource du module"),
 *     @OA\Property(property="module_resource_id", type="integer", description="ID de la ressource du module"),
 *     @OA\Property(property="collection", type="string", description="Collection du fichier"),
 *     @OA\Property(property="size", type="integer", description="Taille du fichier en octets"),
 *     @OA\Property(property="mime_type", type="string", description="Type MIME"),
 *     @OA\Property(property="extension", type="string", description="Extension du fichier"),
 *     @OA\Property(property="file_hash", type="string", description="Hash du fichier"),
 *     @OA\Property(property="metadata", type="object", description="Métadonnées supplémentaires"),
 *     @OA\Property(property="is_locked", type="boolean", description="Si le fichier est verrouillé"),
 *     @OA\Property(property="locked_at", type="string", format="date-time", description="Date de verrouillage"),
 *     @OA\Property(property="locked_by", type="integer", description="ID de l'utilisateur qui a verrouillé"),
 *     @OA\Property(property="download_count", type="integer", description="Nombre de téléchargements"),
 *     @OA\Property(property="last_accessed_at", type="string", format="date-time", description="Dernier accès"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour"),
 *     @OA\Property(property="url", type="string", description="URL du fichier"),
 *     @OA\Property(property="size_for_humans", type="string", description="Taille formatée")
 * )
 */
class File extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'user_id',
        // 'name', // Colonne générée, ne pas inclure dans fillable
        'original_name',
        'stored_name',
        'file_path',
        'description',
        'document_categorie',
        'is_official_document',
        'date_publication',
        // 'path', // Colonne générée, ne pas inclure dans fillable
        'disk',
        'visibility',
        'module_name',
        'module_resource_type',
        'module_resource_id',
        'collection',
        'size',
        'mime_type',
        'extension',
        // 'file_hash', // Colonne non présente dans la table
        'metadata',
        'is_locked',
        'locked_at',
        'locked_by',
        'download_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
        'is_locked' => 'boolean',
        'is_official_document' => 'boolean',
        'date_publication' => 'date',
        'locked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'download_count' => 'integer',
    ];

    /**
     * Le propriétaire du fichier.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * L'utilisateur qui a verrouillé le fichier.
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'locked_by');
    }

    /**
     * Les permissions associées à ce fichier.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(FilePermission::class);
    }

    /**
     * Les partages de ce fichier.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    /**
     * L'historique des activités sur ce fichier.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(FileActivity::class);
    }

    /**
     * Obtenir l'URL complète du fichier.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->visibility === 'public') {
            return Storage::disk($this->disk)->url($this->path);
        }
        
        // Pour les fichiers privés, retourner l'URL de l'API
        return route('api.files.download', ['file' => $this->id]);
    }

    /**
     * Obtenir la taille formatée.
     */
    public function getSizeForHumansAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Vérifie si le fichier existe physiquement.
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Vérifie si le fichier est une image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Vérifie si le fichier est un document.
     */
    public function isDocument(): bool
    {
        $documentTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument'];
        
        foreach ($documentTypes as $type) {
            if (str_starts_with($this->mime_type, $type)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Incrémente le compteur de téléchargements.
     */
    public function incrementDownloadCount(): void
    {
        //$this->update(['last_accessed_at' => now()]);
    }

    /**
     * Scope pour les fichiers publics.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope pour les fichiers privés.
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    /**
     * Scope pour les fichiers d'un module spécifique.
     */
    public function scopeForModule($query, string $moduleName, ?string $resourceType = null, ?int $resourceId = null)
    {
        $query->where('module_name', $moduleName);
        
        if ($resourceType !== null) {
            $query->where('module_resource_type', $resourceType);
        }
        
        if ($resourceId !== null) {
            $query->where('module_resource_id', $resourceId);
        }
        
        return $query;
    }

    /**
     * Scope pour les fichiers d'une collection.
     */
    public function scopeInCollection($query, string $collection)
    {
        return $query->where('collection', $collection);
    }

    /**
     * Scope pour les fichiers non verrouillés.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Scope pour les fichiers verrouillés.
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope pour rechercher par nom.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('original_name', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }

    /**
     * Scope pour les documents officiels.
     */
    public function scopeOfficialDocuments($query)
    {
        return $query->where('is_official_document', true);
    }

    /**
     * Scope pour filtrer par catégorie de document.
     */
    public function scopeByCategorie($query, string $categorie)
    {
        return $query->where('document_categorie', $categorie);
    }
}

<?php

namespace App\Modules\Stockage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Traits\HasUuid;

/**
 * @OA\Schema(
 *     schema="FileShare",
 *     title="File Share",
 *     description="Modèle représentant un partage de fichier",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="file_id", type="integer", description="ID du fichier partagé"),
 *     @OA\Property(property="token", type="string", description="Token unique du partage"),
 *     @OA\Property(property="password_hash", type="string", nullable=true, description="Hash du mot de passe"),
 *     @OA\Property(property="allow_download", type="boolean", description="Autorise le téléchargement"),
 *     @OA\Property(property="allow_preview", type="boolean", description="Autorise l'aperçu"),
 *     @OA\Property(property="max_downloads", type="integer", nullable=true, description="Nombre maximum de téléchargements"),
 *     @OA\Property(property="download_count", type="integer", description="Nombre de téléchargements effectués"),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, description="Date d'expiration"),
 *     @OA\Property(property="created_by", type="integer", description="ID du créateur du partage"),
 *     @OA\Property(property="is_active", type="boolean", description="Si le partage est actif"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour"),
 *     @OA\Property(property="share_url", type="string", description="URL complète du partage"),
 *     @OA\Property(
 *         property="file",
 *         ref="#/components/schemas/File",
 *         description="Fichier partagé"
 *     ),
 *     @OA\Property(
 *         property="creator",
 *         ref="#/components/schemas/User",
 *         description="Créateur du partage"
 *     )
 * )
 */
class FileShare extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'file_id',
        'token',
        'password_hash',
        'allow_download',
        'allow_preview',
        'max_downloads',
        'download_count',
        'expires_at',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'allow_download' => 'boolean',
        'allow_preview' => 'boolean',
        'max_downloads' => 'integer',
        'download_count' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Génère automatiquement un token unique lors de la création.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = Str::random(64);
            }
        });
    }

    /**
     * Le fichier partagé.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Le créateur du partage.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Obtenir l'URL complète du partage.
     */
    public function getShareUrlAttribute(): string
    {
        return route('api.files.share.access', ['token' => $this->token]);
    }

    /**
     * Vérifie si le partage a expiré.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Vérifie si le partage a atteint la limite de téléchargements.
     */
    public function hasReachedDownloadLimit(): bool
    {
        if (!$this->max_downloads) {
            return false;
        }

        return $this->download_count >= $this->max_downloads;
    }

    /**
     * Vérifie si le partage est valide (actif, non expiré, non limité).
     */
    public function isValid(): bool
    {
        return $this->is_active 
            && !$this->isExpired() 
            && !$this->hasReachedDownloadLimit();
    }

    /**
     * Vérifie le mot de passe du partage.
     */
    public function checkPassword(?string $password): bool
    {
        if (!$this->password_hash) {
            return true; // Pas de mot de passe requis
        }

        if (!$password) {
            return false;
        }

        return password_verify($password, $this->password_hash);
    }

    /**
     * Incrémente le compteur de téléchargements.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Scope pour les partages actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope pour les partages expirés.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope pour les partages par token.
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }
}

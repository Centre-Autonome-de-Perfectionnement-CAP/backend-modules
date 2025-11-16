<?php

namespace App\Modules\Stockage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

/**
 * @OA\Schema(
 *     schema="FilePermission",
 *     title="File Permission",
 *     description="Modèle représentant une permission sur un fichier",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="file_id", type="integer", description="ID du fichier"),
 *     @OA\Property(property="user_id", type="integer", nullable=true, description="ID de l'utilisateur (null si permission par rôle)"),
 *     @OA\Property(property="role_id", type="integer", nullable=true, description="ID du rôle (null si permission individuelle)"),
 *     @OA\Property(property="permission_type", type="string", enum={"read", "write", "delete", "share", "admin"}, description="Type de permission"),
 *     @OA\Property(property="granted_by", type="integer", description="ID de l'utilisateur qui a accordé la permission"),
 *     @OA\Property(property="granted_at", type="string", format="date-time", description="Date d'attribution"),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, description="Date d'expiration"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour"),
 *     @OA\Property(
 *         property="file",
 *         ref="#/components/schemas/File",
 *         description="Fichier associé"
 *     ),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User",
 *         description="Utilisateur bénéficiaire"
 *     ),
 *     @OA\Property(
 *         property="role",
 *         ref="#/components/schemas/Role",
 *         description="Rôle bénéficiaire"
 *     )
 * )
 */
class FilePermission extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'file_id',
        'user_id',
        'role_id',
        'permission_type',
        'granted_by',
        'granted_at',
        'expires_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Le fichier concerné.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * L'utilisateur bénéficiaire (si permission individuelle).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Le rôle bénéficiaire (si permission par rôle).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * L'utilisateur qui a accordé la permission.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'granted_by');
    }

    /**
     * Vérifie si la permission a expiré.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Vérifie si la permission est active.
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Scope pour les permissions actives (non expirées).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope pour les permissions expirées.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope pour les permissions d'un utilisateur spécifique.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les permissions d'un rôle spécifique.
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope pour un type de permission spécifique.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('permission_type', $type);
    }
}

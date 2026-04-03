<?php

namespace App\Modules\Stockage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="FileActivity",
 *     title="File Activity",
 *     description="Modèle représentant une activité sur un fichier",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="file_id", type="integer", description="ID du fichier"),
 *     @OA\Property(property="user_id", type="integer", nullable=true, description="ID de l'utilisateur"),
 *     @OA\Property(property="activity_type", type="string", description="Type d'activité"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Description de l'activité"),
 *     @OA\Property(property="metadata", type="object", nullable=true, description="Métadonnées supplémentaires"),
 *     @OA\Property(property="ip_address", type="string", description="Adresse IP"),
 *     @OA\Property(property="user_agent", type="string", description="User agent"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(
 *         property="file",
 *         ref="#/components/schemas/File",
 *         description="Fichier associé"
 *     ),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User",
 *         description="Utilisateur associé"
 *     )
 * )
 */
class FileActivity extends Model
{
    use HasFactory, HasUuid;

    const UPDATED_AT = null; // Pas de colonne updated_at

    protected $fillable = [
        'file_id',
        'user_id',
        'action', // Nom correct de la colonne
        // 'description', // Colonne non présente dans la table
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Le fichier concerné.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * L'utilisateur qui a effectué l'action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Scope pour filtrer par type d'activité.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('action', $type);
    }

    /**
     * Scope pour les activités d'un utilisateur.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les activités récentes.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Crée une nouvelle activité.
     */
    public static function log(
        int $fileId,
        ?int $userId,
        string $action,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'uuid' => Str::uuid(),
            'file_id' => $fileId,
            'user_id' => $userId,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}

<?php

namespace App\Modules\Stockage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="Role",
 *     title="Role",
 *     description="Modèle représentant un rôle utilisateur",
 *     @OA\Property(property="id", type="integer", description="ID unique"),
 *     @OA\Property(property="name", type="string", description="Nom du rôle"),
 *     @OA\Property(property="display_name", type="string", description="Nom d'affichage"),
 *     @OA\Property(property="description", type="string", description="Description du rôle"),
 *     @OA\Property(property="is_system", type="boolean", description="Si c'est un rôle système"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de mise à jour")
 * )
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Les permissions associées à ce rôle.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Les utilisateurs ayant ce rôle.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            'role_user'
        )->withPivot('assigned_at')->withTimestamps();
    }

    /**
     * Les permissions de fichiers liées à ce rôle.
     */
    public function filePermissions(): HasMany
    {
        return $this->hasMany(FilePermission::class);
    }

    /**
     * Vérifie si le rôle a une permission spécifique.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Attribue une ou plusieurs permissions au rôle.
     */
    public function givePermissionTo(string|array|Permission $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        
        $permissionIds = collect($permissions)->map(function ($permission) {
            if ($permission instanceof Permission) {
                return $permission->id;
            }
            
            return Permission::where('name', $permission)->first()?->id;
        })->filter();

        $this->permissions()->syncWithoutDetaching($permissionIds);

        return $this;
    }

    /**
     * Révoque une ou plusieurs permissions du rôle.
     */
    public function revokePermissionTo(string|array|Permission $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        
        $permissionIds = collect($permissions)->map(function ($permission) {
            if ($permission instanceof Permission) {
                return $permission->id;
            }
            
            return Permission::where('name', $permission)->first()?->id;
        })->filter();

        $this->permissions()->detach($permissionIds);

        return $this;
    }

    /**
     * Scope pour les rôles non-système (supprimables).
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope pour les rôles système.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}

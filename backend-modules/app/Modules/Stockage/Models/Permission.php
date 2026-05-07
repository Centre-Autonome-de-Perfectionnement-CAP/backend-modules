<?php

namespace App\Modules\Stockage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasUuid;

class Permission extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'resource',
        'action',
    ];

    /**
     * Les rôles qui ont cette permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * Scope pour filtrer par module.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope pour filtrer par ressource.
     */
    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope pour filtrer par action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Génère le nom complet de la permission.
     */
    public static function generateName(string $module, string $resource, string $action): string
    {
        return "{$module}.{$resource}.{$action}";
    }
}

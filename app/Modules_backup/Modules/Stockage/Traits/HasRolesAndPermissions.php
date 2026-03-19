<?php

namespace App\Modules\Stockage\Traits;

use App\Modules\Stockage\Models\Role;
use App\Modules\Stockage\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Trait HasRolesAndPermissions
 * 
 * À utiliser dans le modèle User pour gérer les rôles et permissions
 */
trait HasRolesAndPermissions
{
    /**
     * Les rôles de l'utilisateur.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * Assigne un rôle à l'utilisateur.
     */
    public function assignRole(string|Role $role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        if (!$this->hasRole($role)) {
            $this->roles()->attach($role->id, ['assigned_at' => now()]);
        }

        return $this;
    }

    /**
     * Retire un rôle de l'utilisateur.
     */
    public function removeRole(string|Role $role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);

        return $this;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     */
    public function hasRole(string|Role $role): bool
    {
        if (is_string($role)) {
            return $this->roles()->where('name', $role)->exists();
        }

        return $this->roles()->where('id', $role->id)->exists();
    }

    /**
     * Vérifie si l'utilisateur a l'un des rôles spécifiés.
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a tous les rôles spécifiés.
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Récupère toutes les permissions de l'utilisateur via ses rôles.
     */
    public function getAllPermissions()
    {
        return Permission::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles->pluck('id'));
        })->get();
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->getAllPermissions()->contains('name', $permissionName);
    }

    /**
     * Vérifie si l'utilisateur peut effectuer une action sur une ressource.
     */
    public function can($ability, $arguments = []): bool
    {
        // Déléguer à Laravel Gate si disponible
        if (method_exists(parent::class, 'can')) {
            return parent::can($ability, $arguments);
        }

        return false;
    }
}

<?php

namespace App\Modules\Stockage\Policies;

use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Détermine si l'utilisateur peut voir la liste des fichiers.
     */
    public function viewAny($user): bool
    {
        return true; // Tout utilisateur authentifié peut voir la liste de ses fichiers
    }

    /**
     * Détermine si l'utilisateur peut voir un fichier.
     */
    public function view($user, File $file): bool
    {
        // Si le fichier est verrouillé, seul le propriétaire et celui qui l'a verrouillé peuvent y accéder
        if ($file->is_locked && $file->locked_by !== $user->id && $file->user_id !== $user->id) {
            return false;
        }

        return $this->permissionService->userCan($file, $user->id, 'read');
    }

    /**
     * Détermine si l'utilisateur peut créer un fichier.
     */
    public function create($user): bool
    {
        return true; // Tout utilisateur authentifié peut uploader des fichiers
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour un fichier.
     */
    public function update($user, File $file): bool
    {
        // Si le fichier est verrouillé par quelqu'un d'autre
        if ($file->is_locked && $file->locked_by !== $user->id) {
            return false;
        }

        return $this->permissionService->userCan($file, $user->id, 'write');
    }

    /**
     * Détermine si l'utilisateur peut supprimer un fichier.
     */
    public function delete($user, File $file): bool
    {
        return $this->permissionService->userCan($file, $user->id, 'delete');
    }

    /**
     * Détermine si l'utilisateur peut restaurer un fichier.
     */
    public function restore($user, File $file): bool
    {
        return $file->user_id === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut supprimer définitivement un fichier.
     */
    public function forceDelete($user, File $file): bool
    {
        return $file->user_id === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut télécharger un fichier.
     */
    public function download($user, File $file): bool
    {
        return $this->view($user, $file);
    }

    /**
     * Détermine si l'utilisateur peut partager un fichier.
     */
    public function share($user, File $file): bool
    {
        return $this->permissionService->userCan($file, $user->id, 'share');
    }

    /**
     * Détermine si l'utilisateur peut gérer les permissions d'un fichier.
     */
    public function managePermissions($user, File $file): bool
    {
        return $this->permissionService->userCan($file, $user->id, 'admin');
    }

    /**
     * Détermine si l'utilisateur peut verrouiller/déverrouiller un fichier.
     */
    public function lock($user, File $file): bool
    {
        // Seul le propriétaire peut verrouiller/déverrouiller
        // Ou celui qui a verrouillé peut déverrouiller
        return $file->user_id === $user->id || $file->locked_by === $user->id;
    }

    /**
     * Détermine si l'utilisateur peut changer la visibilité d'un fichier.
     */
    public function changeVisibility($user, File $file): bool
    {
        // Seul le propriétaire peut changer la visibilité
        return $file->user_id === $user->id;
    }
}

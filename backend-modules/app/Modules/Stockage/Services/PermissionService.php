<?php

namespace App\Modules\Stockage\Services;

use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Models\FilePermission;
use App\Modules\Stockage\Models\FileActivity;
use App\Modules\Stockage\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    /**
     * Vérifie si un utilisateur a une permission spécifique sur un fichier.
     *
     * @param File $file
     * @param int $userId
     * @param string $permissionType (read, write, delete, share, admin)
     * @return bool
     */
    public function userCan(File $file, int $userId, string $permissionType = 'read'): bool
    {
        // Le propriétaire a tous les droits
        if ($file->user_id === $userId) {
            return true;
        }

        // Si le fichier est public, tout le monde peut le lire
        if ($file->visibility === 'public' && $permissionType === 'read') {
            return true;
        }

        // Vérifier les permissions directes de l'utilisateur
        $hasDirectPermission = FilePermission::where('file_id', $file->id)
            ->where('user_id', $userId)
            ->where('permission_type', $permissionType)
            ->active()
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        // Vérifier les permissions via les rôles
        $userRoles = DB::table('role_user')
            ->where('user_id', $userId)
            ->pluck('role_id');

        if ($userRoles->isEmpty()) {
            return false;
        }

        return FilePermission::where('file_id', $file->id)
            ->whereIn('role_id', $userRoles)
            ->where('permission_type', $permissionType)
            ->active()
            ->exists();
    }

    /**
     * Vérifie si un utilisateur peut accéder à un fichier (au moins en lecture).
     *
     * @param File $file
     * @param int $userId
     * @return bool
     */
    public function canAccess(File $file, int $userId): bool
    {
        return $this->userCan($file, $userId, 'read');
    }

    /**
     * Accorde une permission à un utilisateur sur un fichier.
     *
     * @param File $file
     * @param int $userId
     * @param string $permissionType
     * @param int $grantedBy
     * @param \DateTime|null $expiresAt
     * @return FilePermission
     */
    public function grantUserPermission(
        File $file,
        int $userId,
        string $permissionType,
        int $grantedBy,
        ?\DateTime $expiresAt = null
    ): FilePermission {
        return DB::transaction(function () use ($file, $userId, $permissionType, $grantedBy, $expiresAt) {
            // Créer ou mettre à jour la permission
            $permission = FilePermission::updateOrCreate(
                [
                    'file_id' => $file->id,
                    'user_id' => $userId,
                    'permission_type' => $permissionType,
                ],
                [
                    'granted_by' => $grantedBy,
                    'granted_at' => now(),
                    'expires_at' => $expiresAt,
                ]
            );

            // Logger l'activité
            FileActivity::log(
                $file->id,
                $grantedBy,
                'permission_granted',
                "Permission '{$permissionType}' accordée à l'utilisateur #{$userId}"
            );

            return $permission;
        });
    }

    /**
     * Accorde une permission à un rôle sur un fichier.
     *
     * @param File $file
     * @param int $roleId
     * @param string $permissionType
     * @param int $grantedBy
     * @param \DateTime|null $expiresAt
     * @return FilePermission
     */
    public function grantRolePermission(
        File $file,
        int $roleId,
        string $permissionType,
        int $grantedBy,
        ?\DateTime $expiresAt = null
    ): FilePermission {
        return DB::transaction(function () use ($file, $roleId, $permissionType, $grantedBy, $expiresAt) {
            // Vérifier que le rôle existe
            if (!Role::find($roleId)) {
                throw new \App\Exceptions\BusinessException(
                   message: "Le rôle #{$roleId} n'existe pas",
                   errorCode: 'ROLE_NOT_FOUND',
                   statusCode: 404
               );
            }

            // Créer ou mettre à jour la permission
            $permission = FilePermission::updateOrCreate(
                [
                    'file_id' => $file->id,
                    'role_id' => $roleId,
                    'permission_type' => $permissionType,
                ],
                [
                    'granted_by' => $grantedBy,
                    'granted_at' => now(),
                    'expires_at' => $expiresAt,
                ]
            );

            // Logger l'activité
            FileActivity::log(
                $file->id,
                $grantedBy,
                'permission_granted',
                "Permission '{$permissionType}' accordée au rôle #{$roleId}"
            );

            return $permission;
        });
    }

    /**
     * Révoque une permission utilisateur.
     *
     * @param File $file
     * @param int $userId
     * @param string $permissionType
     * @param int $revokedBy
     * @return bool
     */
    public function revokeUserPermission(
        File $file,
        int $userId,
        string $permissionType,
        int $revokedBy
    ): bool {
        return DB::transaction(function () use ($file, $userId, $permissionType, $revokedBy) {
            $deleted = FilePermission::where('file_id', $file->id)
                ->where('user_id', $userId)
                ->where('permission_type', $permissionType)
                ->delete();

            if ($deleted) {
                // Logger l'activité
                FileActivity::log(
                    $file->id,
                    $revokedBy,
                    'permission_revoked',
                    "Permission '{$permissionType}' révoquée pour l'utilisateur #{$userId}"
                );
            }

            return $deleted > 0;
        });
    }

    /**
     * Révoque une permission rôle.
     *
     * @param File $file
     * @param int $roleId
     * @param string $permissionType
     * @param int $revokedBy
     * @return bool
     */
    public function revokeRolePermission(
        File $file,
        int $roleId,
        string $permissionType,
        int $revokedBy
    ): bool {
        return DB::transaction(function () use ($file, $roleId, $permissionType, $revokedBy) {
            $deleted = FilePermission::where('file_id', $file->id)
                ->where('role_id', $roleId)
                ->where('permission_type', $permissionType)
                ->delete();

            if ($deleted) {
                // Logger l'activité
                FileActivity::log(
                    $file->id,
                    $revokedBy,
                    'permission_revoked',
                    "Permission '{$permissionType}' révoquée pour le rôle #{$roleId}"
                );
            }

            return $deleted > 0;
        });
    }

    /**
     * Récupère toutes les permissions d'un fichier.
     *
     * @param File $file
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFilePermissions(File $file)
    {
        return FilePermission::where('file_id', $file->id)
            ->with(['user', 'role'])
            ->active()
            ->get();
    }

    /**
     * Récupère les fichiers accessibles par un utilisateur.
     *
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleFiles(int $userId, array $filters = [])
    {
        // Fichiers dont l'utilisateur est propriétaire
        $ownedFiles = File::where('user_id', $userId);

        // Fichiers publics
        $publicFiles = File::public();

        // Fichiers avec permission directe
        $permittedFileIds = FilePermission::where('user_id', $userId)
            ->active()
            ->pluck('file_id');

        // Fichiers avec permission via rôle
        $userRoles = DB::table('role_user')
            ->where('user_id', $userId)
            ->pluck('role_id');

        $rolePermittedFileIds = FilePermission::whereIn('role_id', $userRoles)
            ->active()
            ->pluck('file_id');

        // Combiner tous les IDs
        $allFileIds = $permittedFileIds->merge($rolePermittedFileIds)->unique();

        // Construire la requête finale
        $query = File::where(function ($q) use ($userId, $allFileIds) {
            $q->where('user_id', $userId) // Fichiers propres
              ->orWhere('visibility', 'public') // Fichiers publics
              ->orWhereIn('id', $allFileIds); // Fichiers avec permissions
        });

        // Appliquer les filtres
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

    /**
     * Nettoie les permissions expirées.
     *
     * @return int Nombre de permissions supprimées
     */
    public function cleanExpiredPermissions(): int
    {
        return FilePermission::expired()->delete();
    }
}

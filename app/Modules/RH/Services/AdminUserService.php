<?php

namespace App\Modules\RH\Services;

use App\Models\User;
use App\Modules\Stockage\Services\FileStorageService;
use App\Services\PasswordGeneratorService;
use App\Services\StringUtilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminUserService
{
    public function __construct(
        protected FileStorageService $fileStorageService,
        protected PasswordGeneratorService $passwordGenerator
    ) {}

    /**
     * Récupérer la liste des utilisateurs administratifs avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = User::query()->with(['roles']);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['role_id'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('roles.id', $filters['role_id']);
            });
        }
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer un nouveau membre de l'administration
     */
    public function create(array $data, int $userId, $ribFile = null, $ifuFile = null, $photoFile = null): User
    {
        return DB::transaction(function () use ($data, $ribFile, $ifuFile, $photoFile, $userId) {
            $data['password'] = Hash::make($this->passwordGenerator->generate());

            if (!empty($data['bank'])) {
                $data['bank'] = StringUtilityService::capitalize($data['bank']);
            }

            if ($ribFile) {
                $uploadedRib = $this->fileStorageService->uploadFile(
                    uploadedFile: $ribFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'rib',
                    moduleName: 'RH',
                    moduleResourceType: 'AdminUser',
                    metadata: ['type' => 'rib']
                );
                $data['rib'] = $uploadedRib->id;
            }

            if ($ifuFile) {
                $uploadedIfu = $this->fileStorageService->uploadFile(
                    uploadedFile: $ifuFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'ifu',
                    moduleName: 'RH',
                    moduleResourceType: 'AdminUser',
                    metadata: ['type' => 'ifu']
                );
                $data['ifu'] = $uploadedIfu->id;
            }
            if ($photoFile) {
                $uploadedPhoto = $this->fileStorageService->uploadFile(
                    uploadedFile: $photoFile,
                    userId: $userId,
                    visibility: 'public',
                    collection: 'photos',
                    moduleName: 'RH',
                    moduleResourceType: 'AdminUser',
                    metadata: ['type' => 'photo']
                );
                $data['photo'] = $uploadedPhoto->id;
            }

            $user = User::create($data);

            if (!empty($data['role_id'])) {
                $user->roles()->attach($data['role_id']);
            } elseif (!empty($data['role_ids']) && is_array($data['role_ids'])) {
                $user->roles()->attach($data['role_ids']);
            }

            if (!empty($data['rib'])) {
                $uploadedRib->update(['module_resource_id' => $user->id]);
            }

            if (!empty($data['ifu'])) {
                $uploadedIfu->update(['module_resource_id' => $user->id]);
            }

            if (!empty($data['photo'])) {
                $uploadedPhoto->update(['module_resource_id' => $user->id]);
            }

            Log::info('Utilisateur administratif créé', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return $user;
        });
    }

    /**
     * Récupérer un utilisateur par ID
     */
    public function getById(int $id): ?User
    {
        return User::with(['roles'])->find($id);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(User $user, array $data, int $userId, $ribFile = null, $ifuFile = null, $photoFile = null): User
    {
        return DB::transaction(function () use ($user, $data, $ribFile, $ifuFile, $photoFile, $userId) {
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
            if (!empty($data['bank'])) {
                $data['bank'] = StringUtilityService::capitalize($data['bank']);
            }
            if ($ribFile) {
                $uploadedRib = $this->fileStorageService->uploadFile(
                    uploadedFile: $ribFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'rib',
                    moduleName: 'RH',
                    moduleResourceType: 'AdminUser',
                    moduleResourceId: $user->id,
                    metadata: ['type' => 'rib']
                );
                $data['rib'] = $uploadedRib->id;
            }
            if ($ifuFile) {
                $uploadedIfu = $this->fileStorageService->uploadFile(
                    uploadedFile: $ifuFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'ifu',
                    moduleName: 'RH',
                    moduleResourceType: 'AdminUser',
                    moduleResourceId: $user->id,
                    metadata: ['type' => 'ifu']
                );
                $data['ifu'] = $uploadedIfu->id;
            }
            if ($photoFile) {
                $uploadedPhoto = $this->fileStorageService->uploadFile(
                    uploadedFile: $photoFile,
                    userId: $userId,
                    visibility: 'public',
                    collection: 'photos',
                    moduleName: 'RH',
                    moduleResourceType: 'AdminUser',
                    moduleResourceId: $user->id,
                    metadata: ['type' => 'photo']
                );
                $data['photo'] = $uploadedPhoto->id;
            }
            $user->update($data);
            if (!empty($data['role_id'])) {
                $user->roles()->sync([$data['role_id']]);
            } elseif (isset($data['role_ids']) && is_array($data['role_ids'])) {
                $user->roles()->sync($data['role_ids']);
            }

            Log::info('Utilisateur administratif mis à jour', [
                'user_id' => $user->id,
            ]);

            return $user->fresh(['roles']);
        });
    }

    /**
     * Supprimer un utilisateur
     */
    public function delete(User $user): bool
    {
        try {
            $user->delete();

            Log::info('Utilisateur administratif supprimé', [
                'user_id' => $user->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de l\'utilisateur', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Attacher un rôle à un utilisateur
     */
    public function attachRole(User $user, int $roleId): void
    {
        $user->roles()->syncWithoutDetaching([$roleId]);

        Log::info('Rôle attaché à l\'utilisateur', [
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);
    }

    /**
     * Détacher un rôle d'un utilisateur
     */
    public function detachRole(User $user, int $roleId): void
    {
        $user->roles()->detach($roleId);

        Log::info('Rôle détaché de l\'utilisateur', [
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);
    }

    /**
     * Récupérer les statistiques des utilisateurs
     */
    public function getStatistics(): array
    {
        return [
            'total_admin_users' => User::count(),
            'total_professors' => \App\Modules\RH\Models\Professor::count(),
            'active_professors' => \App\Modules\RH\Models\Professor::where('status', 'active')->count(),
        ];
    }
}

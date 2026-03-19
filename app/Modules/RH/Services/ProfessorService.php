<?php

namespace App\Modules\RH\Services;

use App\Modules\RH\Models\Professor;
use App\Modules\Stockage\Services\FileStorageService;
use App\Services\PasswordGeneratorService;
use App\Services\StringUtilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class ProfessorService
{
    public function __construct(
        protected FileStorageService $fileStorageService,
        protected PasswordGeneratorService $passwordGenerator
    ) {}

    /**
     * Récupérer la liste des professeurs avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Professor::query()->with(['grade']);

        // Recherche
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // ⚠️ CORRIGÉ : statut au lieu de statut
        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['grade_id'])) {
            $query->where('grade_id', $filters['grade_id']);
        }

        if (!empty($filters['bank'])) {
            $query->where('bank', $filters['bank']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Créer un professeur
     */
    public function create(array $data, int $userId, $ribFile = null, $ifuFile = null): Professor
    {
        return DB::transaction(function () use ($data, $ribFile, $ifuFile, $userId) {

            // Mot de passe
            $data['password'] = Hash::make('password');

            // UUID
            $data['uuid'] = Str::uuid();

            // Rôle par défaut
            if (empty($data['role_id'])) {
                $data['role_id'] = 6;
            }

            // Capitaliser banque
            if (!empty($data['bank'])) {
                $data['bank'] = StringUtilityService::capitalize($data['bank']);
            }

            // Upload RIB
            if ($ribFile) {
                $uploadedRib = $this->fileStorageService->uploadFile(
                    uploadedFile: $ribFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'rib',
                    moduleName: 'RH',
                    moduleResourceType: 'Professor',
                    metadata: ['type' => 'rib']
                );

                $data['rib'] = $uploadedRib->id;
            }

            // Upload IFU
            if ($ifuFile) {
                $uploadedIfu = $this->fileStorageService->uploadFile(
                    uploadedFile: $ifuFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'ifu',
                    moduleName: 'RH',
                    moduleResourceType: 'Professor',
                    metadata: ['type' => 'ifu']
                );

                $data['ifu'] = $uploadedIfu->id;
            }

            $professor = Professor::create($data);

            // Lier fichiers après création
            if (isset($uploadedRib)) {
                $uploadedRib->update(['module_resource_id' => $professor->id]);
            }

            if (isset($uploadedIfu)) {
                $uploadedIfu->update(['module_resource_id' => $professor->id]);
            }

            Log::info('Professeur créé', [
                'id' => $professor->id,
                'email' => $professor->email,
            ]);

            return $professor;
        });
    }

    /**
     * Mettre à jour un professeur
     */
    public function update(Professor $professor, array $data, int $userId, $ribFile = null, $ifuFile = null): Professor
    {
        return DB::transaction(function () use ($professor, $data, $ribFile, $ifuFile, $userId) {

            // Mot de passe
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            // Capitaliser banque
            if (!empty($data['bank'])) {
                $data['bank'] = StringUtilityService::capitalize($data['bank']);
            }

            // Upload RIB
            if ($ribFile) {
                $uploadedRib = $this->fileStorageService->uploadFile(
                    uploadedFile: $ribFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'rib',
                    moduleName: 'RH',
                    moduleResourceType: 'Professor',
                    moduleResourceId: $professor->id,
                    metadata: ['type' => 'rib']
                );

                $data['rib'] = $uploadedRib->id;
            }

            // Upload IFU
            if ($ifuFile) {
                $uploadedIfu = $this->fileStorageService->uploadFile(
                    uploadedFile: $ifuFile,
                    userId: $userId,
                    visibility: 'private',
                    collection: 'ifu',
                    moduleName: 'RH',
                    moduleResourceType: 'Professor',
                    moduleResourceId: $professor->id,
                    metadata: ['type' => 'ifu']
                );

                $data['ifu'] = $uploadedIfu->id;
            }

            $professor->update($data);

            Log::info('Professeur mis à jour', [
                'id' => $professor->id,
            ]);

            return $professor->fresh(['grade']);
        });
    }

    /**
     * Supprimer
     */
    public function delete(Professor $professor): bool
    {
        try {
            $professor->delete();

            Log::info('Professeur supprimé', [
                'id' => $professor->id,
            ]);

            return true;

        } catch (Exception $e) {

            Log::error('Erreur suppression professeur', [
                'id' => $professor->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Actifs
     */
    public function getActive()
    {
        return Professor::where('statut', 'actif')
            ->with('grade')
            ->get();
    }

    /**
     * Changer statut
     */
    public function changestatut(Professor $professor, string $statut): Professor
    {
        $professor->update(['statut' => $statut]);

        return $professor->fresh();
    }
}

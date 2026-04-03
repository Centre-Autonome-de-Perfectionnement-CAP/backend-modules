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

        // Filtre par statut
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtre par grade
        if (!empty($filters['grade_id'])) {
            $query->where('grade_id', $filters['grade_id']);
        }

        // Filtre par banque
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
     * Créer un nouveau professeur
     */
    public function create(array $data, int $userId, $ribFile = null, $ifuFile = null): Professor
    {
        return DB::transaction(function () use ($data, $ribFile, $ifuFile, $userId) {
            // $data['password'] = Hash::make($this->passwordGenerator->generate());
            $data['password'] = Hash::make('password');
            $data['uuid'] = Str::uuid();
            
            // Définir le rôle par défaut "Professeur" (ID: 6) si non fourni
            if (empty($data['role_id'])) {
                $data['role_id'] = 6;
            }
            
            // Capitaliser le nom de la banque
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
                    moduleResourceType: 'Professor',
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
                    moduleResourceType: 'Professor',
                    metadata: ['type' => 'ifu']
                );
                $data['ifu'] = $uploadedIfu->id;
            }

            $professor = Professor::create($data);
            if (!empty($data['rib'])) {
                $uploadedRib->update(['module_resource_id' => $professor->id]);
            }

            if (!empty($data['ifu'])) {
                $uploadedIfu->update(['module_resource_id' => $professor->id]);
            }

            Log::info('Professeur créé', [
                'professor_id' => $professor->id,
                'email' => $professor->email,
            ]);

            return $professor;
        });
    }

    /**
     * Récupérer un professeur par ID
     */
    public function getById(int $id): ?Professor
    {
        return Professor::with(['grade'])->find($id);
    }

    /**
     * Mettre à jour un professeur
     */
    public function update(Professor $professor, array $data, int $userId, $ribFile = null, $ifuFile = null): Professor
    {
        return DB::transaction(function () use ($professor, $data, $ribFile, $ifuFile, $userId) {
            // Hasher le mot de passe si fourni
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
            
            // Capitaliser le nom de la banque
            if (!empty($data['bank'])) {
                $data['bank'] = StringUtilityService::capitalize($data['bank']);
            }

            // Upload RIB si fourni
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

            // Upload IFU si fourni
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

            // Mettre à jour le professeur
            $professor->update($data);

            Log::info('Professeur mis à jour', [
                'professor_id' => $professor->id,
            ]);

            return $professor->fresh(['grade']);
        });
    }

    /**
     * Supprimer un professeur
     */
    public function delete(Professor $professor): bool
    {
        try {
            $professor->delete();

            Log::info('Professeur supprimé', [
                'professor_id' => $professor->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression du professeur', [
                'professor_id' => $professor->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Récupérer les professeurs actifs
     */
    public function getActive()
    {
        return Professor::active()->with(['grade'])->get();
    }

    /**
     * Changer le statut d'un professeur
     */
    public function changeStatus(Professor $professor, string $status): Professor
    {
        $professor->update(['status' => $status]);

        Log::info('Statut du professeur changé', [
            'professor_id' => $professor->id,
            'new_status' => $status,
        ]);

        return $professor->fresh();
    }
}

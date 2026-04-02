<?php

namespace App\Modules\RH\Services;

use App\Modules\RH\Models\Professor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\DB;

class ProfessorService
{
    // ───────────────────────── GET ALL AVEC FILTRES ET PAGINATION
    public function getAll(array $filters, int $perPage)
    {
        $query = Professor::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['grade_id'])) {
            $query->where('grade_id', $filters['grade_id']);
        }

        if (!empty($filters['bank'])) {
            $query->where('bank', $filters['bank']);
        }

        if (!empty($filters['nationality'])) {
            $query->where('nationality', $filters['nationality']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (!empty($filters['profession'])) {
            $query->where('profession', 'like', "%{$filters['profession']}%");
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->with('grade')->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    // ───────────────────────── CREATE PROFESSOR
    public function create(array $data, int $authUserId, $ribFile = null, $ifuFile = null): Professor
    {
        DB::beginTransaction();

        try {
            // Générer UUID si non fourni
            if (!isset($data['uuid'])) {
                $data['uuid'] = (string) Str::uuid();
            }

            // Hash password si fourni
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Upload fichiers
            if ($ribFile) {
                $data['rib'] = $this->storeFile($ribFile, 'ribs');
            }

            if ($ifuFile) {
                $data['ifu'] = $this->storeFile($ifuFile, 'ifus');
            }

            $professor = Professor::create($data);

            DB::commit();

            return $professor->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Erreur création professeur : " . $e->getMessage());
        }
    }

    // ───────────────────────── UPDATE PROFESSOR
    public function update(Professor $professor, array $data, int $authUserId, $ribFile = null, $ifuFile = null): Professor
    {
        DB::beginTransaction();

        try {
            // Hash password si fourni
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Upload fichiers
            if ($ribFile) {
                $this->deleteFile($professor->rib);
                $data['rib'] = $this->storeFile($ribFile, 'ribs');
            }

            if ($ifuFile) {
                $this->deleteFile($professor->ifu);
                $data['ifu'] = $this->storeFile($ifuFile, 'ifus');
            }

            $professor->update($data);

            DB::commit();

            return $professor->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Erreur mise à jour professeur : " . $e->getMessage());
        }
    }

    // ───────────────────────── DELETE PROFESSOR
    public function delete(Professor $professor)
    {
        // Supprimer fichiers si existants
        $this->deleteFile($professor->rib);
        $this->deleteFile($professor->ifu);

        $professor->delete();
    }

    // ───────────────────────── STOCKAGE FICHIERS
    protected function storeFile($file, string $folder): string
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        return $file->storeAs("professors/{$folder}", $filename, 'public');
    }

    protected function deleteFile(?string $path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

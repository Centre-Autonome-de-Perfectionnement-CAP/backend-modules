<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\PendingStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PendingStudentService
{
    /**
     * Récupérer tous les étudiants en attente avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = PendingStudent::query()->with(['entryLevel', 'entryDiploma']);

        // Filtre par statut
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtre par niveau d'entrée
        if (!empty($filters['entry_level_id'])) {
            $query->where('entry_level_id', $filters['entry_level_id']);
        }

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

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Créer un étudiant en attente
     */
    public function create(array $data): PendingStudent
    {
        return DB::transaction(function () use ($data) {
            $pendingStudent = PendingStudent::create([
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'entry_level_id' => $data['entry_level_id'],
                'entry_diploma_id' => $data['entry_diploma_id'],
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            Log::info('Étudiant en attente créé', [
                'pending_student_id' => $pendingStudent->id,
                'email' => $pendingStudent->email,
            ]);

            return $pendingStudent;
        });
    }

    /**
     * Récupérer un étudiant en attente par ID
     */
    public function getById(int $id): ?PendingStudent
    {
        return PendingStudent::with(['entryLevel', 'entryDiploma'])->find($id);
    }

    /**
     * Mettre à jour un étudiant en attente
     */
    public function update(PendingStudent $pendingStudent, array $data): PendingStudent
    {
        return DB::transaction(function () use ($pendingStudent, $data) {
            $pendingStudent->update($data);

            Log::info('Étudiant en attente mis à jour', [
                'pending_student_id' => $pendingStudent->id,
            ]);

            return $pendingStudent->fresh(['entryLevel', 'entryDiploma']);
        });
    }

    /**
     * Supprimer un étudiant en attente
     */
    public function delete(PendingStudent $pendingStudent): bool
    {
        try {
            $pendingStudent->delete();

            Log::info('Étudiant en attente supprimé', [
                'pending_student_id' => $pendingStudent->id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de l\'étudiant en attente', [
                'pending_student_id' => $pendingStudent->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Changer le statut d'un étudiant en attente
     */
    public function changeStatus(PendingStudent $pendingStudent, string $status): PendingStudent
    {
        $pendingStudent->update(['status' => $status]);

        Log::info('Statut de l\'étudiant en attente changé', [
            'pending_student_id' => $pendingStudent->id,
            'new_status' => $status,
        ]);

        return $pendingStudent->fresh();
    }

    /**
     * Récupérer les statistiques
     */
    public function getStatistics(): array
    {
        return [
            'total' => PendingStudent::count(),
            'pending' => PendingStudent::where('status', 'pending')->count(),
            'documents_submitted' => PendingStudent::where('status', 'documents_submitted')->count(),
            'approved' => PendingStudent::where('status', 'approved')->count(),
            'rejected' => PendingStudent::where('status', 'rejected')->count(),
        ];
    }
}

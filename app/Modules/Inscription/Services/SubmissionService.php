<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\DossierSubmission;
use App\Modules\Stockage\Services\FileStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SubmissionService
{
    public function __construct(
        protected FileStorageService $fileStorageService
    ) {}

    /**
     * Récupérer toutes les soumissions avec filtres
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = DossierSubmission::query()->with(['pendingStudent', 'documents']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['pending_student_id'])) {
            $query->where('pending_student_id', $filters['pending_student_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('pendingStudent', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('submitted_at', 'desc')->paginate($perPage);
    }

    /**
     * Créer une soumission avec documents
     */
    public function create(array $data, array $files, int $userId): DossierSubmission
    {
        return DB::transaction(function () use ($data, $files, $userId) {
            // Créer la soumission
            $submission = DossierSubmission::create([
                'pending_student_id' => $data['pending_student_id'],
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Uploader les documents
            foreach ($files as $key => $file) {
                if ($file) {
                    $uploadedFile = $this->fileStorageService->uploadFile(
                        uploadedFile: $file,
                        userId: $userId,
                        visibility: 'private',
                        collection: 'submissions',
                        moduleName: 'Inscription',
                        moduleResourceType: 'DossierSubmission',
                        moduleResourceId: $submission->id,
                        metadata: [
                            'document_type' => $key,
                            'pending_student_id' => $data['pending_student_id'],
                        ]
                    );

                    // Créer l'entrée de document
                    $submission->documents()->create([
                        'file_id' => $uploadedFile->id,
                        'document_type' => $key,
                    ]);
                }
            }

            Log::info('Soumission créée', [
                'submission_id' => $submission->id,
                'pending_student_id' => $data['pending_student_id'],
                'documents_count' => count($files),
            ]);

            return $submission->fresh(['pendingStudent', 'documents']);
        });
    }

    /**
     * Récupérer par ID
     */
    public function getById(int $id): ?DossierSubmission
    {
        return DossierSubmission::with(['pendingStudent', 'documents.file'])->find($id);
    }

    /**
     * Mettre à jour une soumission
     */
    public function update(DossierSubmission $submission, array $data): DossierSubmission
    {
        return DB::transaction(function () use ($submission, $data) {
            $submission->update($data);

            Log::info('Soumission mise à jour', [
                'submission_id' => $submission->id,
            ]);

            return $submission->fresh(['pendingStudent', 'documents']);
        });
    }

    /**
     * Approuver une soumission
     */
    public function approve(DossierSubmission $submission, ?string $comment = null): DossierSubmission
    {
        return DB::transaction(function () use ($submission, $comment) {
            $submission->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'review_comment' => $comment,
            ]);

            // Mettre à jour le status du pending student
            if ($submission->pendingStudent) {
                $submission->pendingStudent->update([
                    'status' => 'approved',
                ]);
            }

            Log::info('Soumission approuvée', [
                'submission_id' => $submission->id,
                'pending_student_id' => $submission->pending_student_id,
            ]);

            return $submission->fresh();
        });
    }

    /**
     * Rejeter une soumission
     */
    public function reject(DossierSubmission $submission, string $reason): DossierSubmission
    {
        return DB::transaction(function () use ($submission, $reason) {
            $submission->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'review_comment' => $reason,
            ]);

            // Mettre à jour le status du pending student
            if ($submission->pendingStudent) {
                $submission->pendingStudent->update([
                    'status' => 'rejected',
                ]);
            }

            Log::info('Soumission rejetée', [
                'submission_id' => $submission->id,
                'pending_student_id' => $submission->pending_student_id,
                'reason' => $reason,
            ]);

            return $submission->fresh();
        });
    }

    /**
     * Supprimer une soumission
     */
    public function delete(DossierSubmission $submission, int $userId): bool
    {
        return DB::transaction(function () use ($submission, $userId) {
            try {
                // Supprimer les fichiers associés
                foreach ($submission->documents as $document) {
                    if ($document->file) {
                        $this->fileStorageService->forceDeleteFile($document->file, $userId);
                    }
                }

                $submission->delete();

                Log::info('Soumission supprimée', [
                    'submission_id' => $submission->id,
                ]);

                return true;
            } catch (Exception $e) {
                Log::error('Erreur suppression soumission', [
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Récupérer les statistiques des soumissions
     */
    public function getStatistics(): array
    {
        return [
            'total' => DossierSubmission::count(),
            'submitted' => DossierSubmission::where('status', 'submitted')->count(),
            'approved' => DossierSubmission::where('status', 'approved')->count(),
            'rejected' => DossierSubmission::where('status', 'rejected')->count(),
            'pending_review' => DossierSubmission::where('status', 'submitted')
                ->whereNull('reviewed_at')
                ->count(),
        ];
    }
}

<?php

namespace App\Modules\Inscription\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\User;
use App\Modules\Inscription\Mail\InformationCorrectionResult;
use App\Modules\Inscription\Models\InformationCorrectionRequest;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InformationCorrectionService
{
    /**
     * Les champs autorisés à être modifiés.
     */
    private const ALLOWED_FIELDS = ['last_name', 'first_names', 'email', 'contacts'];

    /**
     * Retrouve les informations personnelles d'un étudiant par son matricule.
     * Retourne uniquement les 4 champs modifiables.
     */
    public function lookupStudent(string $matricule): array
    {
        $student = Student::where('student_id_number', strtoupper(trim($matricule)))->first();

        if (!$student) {
            throw new ResourceNotFoundException('Aucun étudiant trouvé avec ce matricule.');
        }

        $studentPendingStudent = StudentPendingStudent::where('student_id', $student->id)
            ->with('pendingStudent.personalInformation')
            ->first();

        if (!$studentPendingStudent || !$studentPendingStudent->pendingStudent?->personalInformation) {
            throw new ResourceNotFoundException('Informations personnelles introuvables pour ce matricule.');
        }

        $info = $studentPendingStudent->pendingStudent->personalInformation;

        return [
            'student_id_number' => $student->student_id_number,
            'last_name'         => $info->last_name,
            'first_names'       => $info->first_names,
            'email'             => $info->email,
            'contacts'          => $info->contacts ?? [],
        ];
    }

    /**
     * Soumet une demande de correction d'informations.
     */
    public function submitRequest(string $matricule, array $suggestedValues, ?string $justification): InformationCorrectionRequest
    {
        // Vérifier que les champs proposés font partie des champs autorisés
        $invalidFields = array_diff(array_keys($suggestedValues), self::ALLOWED_FIELDS);
        if (!empty($invalidFields)) {
            throw new BusinessException(
                message: 'Champs non autorisés : ' . implode(', ', $invalidFields),
                errorCode: 'INVALID_FIELDS'
            );
        }

        if (empty($suggestedValues)) {
            throw new BusinessException(
                message: 'Aucune modification proposée.',
                errorCode: 'NO_CHANGES'
            );
        }

        // Snapshot des valeurs actuelles
        $currentData = $this->lookupStudent($matricule);
        $currentValues = array_intersect_key($currentData, $suggestedValues);

        // Vérifier qu'il n'y a pas déjà une demande pending pour ce matricule
        $existing = InformationCorrectionRequest::where('student_id_number', strtoupper(trim($matricule)))
            ->pending()
            ->first();

        if ($existing) {
            throw new BusinessException(
                message: 'Vous avez déjà une demande de correction en attente de traitement.',
                errorCode: 'PENDING_REQUEST_EXISTS'
            );
        }

        return InformationCorrectionRequest::create([
            'student_id_number' => strtoupper(trim($matricule)),
            'current_values'    => $currentValues,
            'suggested_values'  => $suggestedValues,
            'justification'     => $justification,
            'status'            => 'pending',
        ]);
    }

    /**
     * Liste les demandes de correction pour l'admin.
     */
    public function listRequests(array $filters = []): Collection
    {
        $query = InformationCorrectionRequest::with('reviewer')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['student_id_number'])) {
            $query->where('student_id_number', 'like', '%' . $filters['student_id_number'] . '%');
        }

        return $query->get();
    }

    /**
     * Retourne les demandes d'un étudiant par son matricule.
     */
    public function getStudentRequests(string $matricule): Collection
    {
        return InformationCorrectionRequest::where('student_id_number', strtoupper(trim($matricule)))
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Approuve une demande et met à jour PersonalInformation.
     */
    public function approve(InformationCorrectionRequest $correctionRequest, int $reviewerId): void
    {
        DB::transaction(function () use ($correctionRequest, $reviewerId) {
            $student = Student::where('student_id_number', $correctionRequest->student_id_number)->firstOrFail();

            $studentPendingStudent = StudentPendingStudent::where('student_id', $student->id)
                ->with('pendingStudent.personalInformation')
                ->firstOrFail();

            $personalInfo = $studentPendingStudent->pendingStudent->personalInformation;

            if (!$personalInfo) {
                throw new ResourceNotFoundException('PersonalInformation introuvable pour cet étudiant.');
            }

            // Appliquer les modifications
            $personalInfo->update($correctionRequest->suggested_values);

            // Mettre à jour la demande
            $correctionRequest->update([
                'status'      => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            // Envoyer le mail vers le NOUVEL email (s'il a été modifié) ou l'actuel
            $emailTo = $correctionRequest->suggested_values['email'] ?? $personalInfo->fresh()->email;
            $firstName = $correctionRequest->suggested_values['first_names'] ?? $personalInfo->first_names;

            try {
                Mail::to($emailTo)->send(new InformationCorrectionResult([
                    'first_names'      => $firstName,
                    'student_id_number' => $correctionRequest->student_id_number,
                    'status'           => 'approved',
                    'suggested_values' => $correctionRequest->suggested_values,
                    'rejection_reason' => null,
                ]));
            } catch (\Exception $e) {
                Log::error('Échec envoi mail approbation correction: ' . $e->getMessage(), [
                    'correction_id' => $correctionRequest->id,
                ]);
            }

            Log::info('Demande de correction approuvée', [
                'correction_id'     => $correctionRequest->id,
                'student_id_number' => $correctionRequest->student_id_number,
                'reviewed_by'       => $reviewerId,
            ]);
        });
    }

    /**
     * Rejette une demande sans modifier PersonalInformation.
     */
    public function reject(InformationCorrectionRequest $correctionRequest, int $reviewerId, string $rejectionReason): void
    {
        DB::transaction(function () use ($correctionRequest, $reviewerId, $rejectionReason) {
            $correctionRequest->update([
                'status'           => 'rejected',
                'reviewed_by'      => $reviewerId,
                'reviewed_at'      => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            // Mail vers l'email ACTUEL (non modifié)
            $currentEmail = $correctionRequest->current_values['email']
                ?? $this->lookupStudent($correctionRequest->student_id_number)['email'];

            $firstName = $correctionRequest->current_values['first_names'] ?? 'Étudiant(e)';

            try {
                Mail::to($currentEmail)->send(new InformationCorrectionResult([
                    'first_names'       => $firstName,
                    'student_id_number' => $correctionRequest->student_id_number,
                    'status'            => 'rejected',
                    'suggested_values'  => $correctionRequest->suggested_values,
                    'rejection_reason'  => $rejectionReason,
                ]));
            } catch (\Exception $e) {
                Log::error('Échec envoi mail rejet correction: ' . $e->getMessage(), [
                    'correction_id' => $correctionRequest->id,
                ]);
            }

            Log::info('Demande de correction rejetée', [
                'correction_id'     => $correctionRequest->id,
                'student_id_number' => $correctionRequest->student_id_number,
                'reviewed_by'       => $reviewerId,
                'reason'            => $rejectionReason,
            ]);
        });
    }
}

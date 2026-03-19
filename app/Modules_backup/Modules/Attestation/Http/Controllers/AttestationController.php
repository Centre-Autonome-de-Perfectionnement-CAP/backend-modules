<?php

namespace App\Modules\Attestation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attestation\Services\AttestationService;
use App\Modules\Attestation\Http\Requests\{
    GenerateAttestationRequest,
    UpdateStudentNamesRequest,
    GetEligibleStudentsRequest,
    GetEligiblePreparatoryRequest,
    GenerateBulletinRequest,
    GenerateMultiplePreparatoryRequest,
    GenerateMultipleBulletinsRequest,
    GenerateMultipleLicenceRequest
};
use App\Modules\Inscription\Models\{PersonalInformation, StudentPendingStudent};
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttestationController extends Controller
{
    use ApiResponse;

    private AttestationService $attestationService;

    public function __construct(AttestationService $attestationService)
    {
        $this->attestationService = $attestationService;
    }

    /**
     * Récupère les étudiants éligibles pour attestation de succès
     */
    public function getEligibleForSuccess(GetEligibleStudentsRequest $request): JsonResponse
    {
        $students = $this->attestationService->getEligibleStudents(
            $request->academic_year_id,
            $request->department_id,
            $request->cohort,
            $request->search
        );

        return $this->successResponse([
            'students' => $students,
            'total' => $students->count(),
        ], 'Étudiants éligibles récupérés avec succès');
    }

    /**
     * Récupère les étudiants éligibles pour certificat de classes préparatoires
     */
    public function getEligibleForPreparatory(GetEligiblePreparatoryRequest $request): JsonResponse
    {
        $students = $this->attestationService->getEligibleForPreparatoryClass(
            $request->academic_year_id,
            $request->department_id,
            $request->cohort,
            $request->search
        );

        return $this->successResponse([
            'students' => $students,
            'total' => $students->count(),
        ], 'Étudiants éligibles récupérés avec succès');
    }

    /**
     * Génère une attestation de succès
     */
    public function generateSuccess(GenerateAttestationRequest $request)
    {
        try {
            $pdf = $this->attestationService->generateAttestationSucces(
                $request->student_pending_student_id
            );

            return $pdf->stream('attestation-succes.pdf');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Génère un certificat de classes préparatoires
     */
    public function generatePreparatory(GenerateAttestationRequest $request)
    {
        try {
            return $this->attestationService->generateCertificatPreparatoire(
                $request->student_pending_student_id
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Génère plusieurs certificats de classes préparatoires dans un seul PDF
     */
    public function generateMultiplePreparatory(GenerateMultiplePreparatoryRequest $request)
    {
        try {
            return $this->attestationService->generateMultipleCertificatsPreparatoires(
                $request->student_pending_student_ids
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Génère un bulletin
     */
    public function generateBulletin(GenerateBulletinRequest $request)
    {
        try {
            return $this->attestationService->generateBulletin(
                $request->student_pending_student_id,
                $request->academic_year_id
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Génère plusieurs bulletins dans un seul PDF
     */
    public function generateMultipleBulletins(GenerateMultipleBulletinsRequest $request)
    {
        try {
            return $this->attestationService->generateMultipleBulletins(
                $request->bulletins
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Génère une attestation de licence
     */
    public function generateLicence(GenerateAttestationRequest $request)
    {
        try {
            $pdf = $this->attestationService->generateAttestationLicence(
                $request->student_pending_student_id
            );

            return $pdf->stream('attestation-licence.pdf');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Génère plusieurs attestations de licence dans un seul PDF
     */
    public function generateMultipleLicence(GenerateMultipleLicenceRequest $request)
    {
        try {
            return $this->attestationService->generateMultipleAttestationsLicence(
                $request->student_pending_student_ids
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Met à jour les noms d'un étudiant
     */
    public function updateStudentNames(UpdateStudentNamesRequest $request, int $studentPendingStudentId): JsonResponse
    {
        try {
            $studentPendingStudent = StudentPendingStudent::with('pendingStudent.personalInformation')
                ->findOrFail($studentPendingStudentId);
            
            $personalInfo = $studentPendingStudent->pendingStudent->personalInformation;
            
            if (!$personalInfo) {
                return $this->errorResponse('Informations personnelles introuvables', 404);
            }
            
            $personalInfo->update([
                'last_name' => $request->last_name,
                'first_names' => $request->first_names,
            ]);

            return $this->successResponse(
                [
                    'last_name' => $personalInfo->last_name,
                    'first_names' => $personalInfo->first_names,
                ],
                'Noms mis à jour avec succès'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Récupère l'URL de l'acte de naissance
     */
    public function getBirthCertificate(int $studentPendingStudentId): JsonResponse
    {
        try {
            $studentPendingStudent = StudentPendingStudent::with('pendingStudent')
                ->findOrFail($studentPendingStudentId);
            
            $pendingStudent = $studentPendingStudent->pendingStudent;
            
            // Récupérer le fichier de l'acte de naissance depuis le module Stockage
            $birthCertFile = $pendingStudent->files()
                ->where(function($q) {
                    $q->where('collection', 'birth_certificate')
                      ->orWhere('collection', 'acte_naissance')
                      ->orWhere('original_name', 'like', '%acte%naissance%')
                      ->orWhere('original_name', 'like', '%birth%certificate%');
                })
                ->first();
            
            if (!$birthCertFile) {
                return $this->errorResponse('Acte de naissance introuvable', 404);
            }
            
            return $this->successResponse([
                'url' => $birthCertFile->url ?? null,
                'path' => $birthCertFile->path ?? null,
            ], 'Acte de naissance récupéré');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

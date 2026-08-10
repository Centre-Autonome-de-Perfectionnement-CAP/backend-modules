<?php

namespace App\Modules\Attestation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attestation\Services\AttestationService;
use App\Modules\Attestation\Services\AttestationStatusService;
use App\Modules\Attestation\Http\Requests\{
    GenerateAttestationRequest,
    GenerateMultiplePassageRequest,
    UpdateStudentNamesRequest,
    GetEligibleStudentsRequest,
    GetEligiblePreparatoryRequest,
    GenerateBulletinRequest,
    GenerateMultiplePreparatoryRequest,
    GenerateMultipleBulletinsRequest,
    GenerateMultipleLicenceRequest,
    GetEligibleDefinitiveRequest,
    GenerateMultipleDefinitiveRequest,
    GetEligibleInscriptionRequest,
    GenerateInscriptionRequest,
    GenerateMultipleInscriptionRequest
};
use App\Modules\Inscription\Models\{StudentPendingStudent, AcademicYear};
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CORRECTIF (v2) — Thin Controller, basé sur le AttestationController réel
 *
 * Différences avec la v1 livrée précédemment :
 *   - Les méthodes publiques (getStatus, getBulletinStatus, identify,
 *     checkAvailability) retournent EXACTEMENT le même format JSON
 *     qu'avant ({message: ...} en 404, pas de wrapper {success:false}),
 *     pour ne rien casser côté frontend.
 *   - checkAvailability() utilise désormais available()/unavailable()
 *     internes (toujours 200 OK avec {available: bool}), comme l'original
 *     — PAS de StudentEligibilityDTO->toArray() direct qui aurait pu
 *     changer le code HTTP.
 *   - Toutes les routes protégées (éligibilité + génération) sont
 *     identiques à la version précédente : aucune divergence trouvée
 *     dans le code source réel.
 */
class AttestationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AttestationService       $attestationService,
        private readonly AttestationStatusService $statusService,
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // ROUTES PUBLIQUES — Site vitrine
    // ══════════════════════════════════════════════════════════════════════════

    public function getAcademicYears(): JsonResponse
    {
        return response()->json(['success' => true, 'data' =>
            AcademicYear::orderBy('year_start', 'desc')->get(['id', 'academic_year', 'libelle', 'is_current'])
        ]);
    }

    public function getStatus(Request $request): JsonResponse
    {
        $request->validate(['matricule' => 'required|string']);

        try {
            $data = $this->statusService->getAttestationStatus($request->matricule);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function getBulletinStatus(Request $request): JsonResponse
    {
        $request->validate(['matricule' => 'required|string']);

        try {
            $data = $this->statusService->getBulletinStatus($request->matricule);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function identify(Request $request): JsonResponse
    {
        $request->validate(['matricule' => 'required|string', 'academic_year' => 'required|string']);

        try {
            $data = $this->statusService->identify($request->matricule, $request->academic_year);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'     => 'required|string',
            'academic_year' => 'required|string',
            'type'          => 'required|in:inscription,passage,definitive',
        ]);

        $dto = $this->statusService->checkAvailability(
            $request->matricule, $request->academic_year, $request->type
        );

        // Format conservé à l'identique de l'original : toujours 200,
        // {success:true, data:{available: bool, reason?: string}}
        return response()->json(['success' => true, 'data' => $dto->toArray() + ['available' => $dto->available]]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ROUTES PROTÉGÉES — Éligibilité + Génération (progiciel interne)
    // Inchangées : aucune divergence trouvée dans le code source réel.
    // ══════════════════════════════════════════════════════════════════════════

    public function getEligibleForPassage(GetEligibleStudentsRequest $request): JsonResponse
    { $s = $this->attestationService->getEligibleForPassage($request->academic_year_id, $request->department_id, $request->cohort, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }

    public function getEligibleForPreparatory(GetEligiblePreparatoryRequest $request): JsonResponse
    { $s = $this->attestationService->getEligibleForPreparatoryClass($request->academic_year_id, $request->department_id, $request->cohort, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }

    public function getEligibleForDefinitive(GetEligibleDefinitiveRequest $request): JsonResponse
    { $s = $this->attestationService->getEligibleForDefinitive($request->academic_year_id, $request->department_id, $request->cohort, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }

    public function getEligibleForInscription(GetEligibleInscriptionRequest $request): JsonResponse
    { $s = $this->attestationService->getEligibleForInscription($request->academic_year_id, $request->department_id, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }

    public function generatePassage(GenerateAttestationRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateAttestationPassage($request->student_pending_student_id)); }

    public function generatePreparatory(GenerateAttestationRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateCertificatPreparatoire($request->student_pending_student_id)); }

    public function generateDefinitive(GenerateAttestationRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateAttestationDefinitive($request->student_pending_student_id)); }

    public function generateInscription(GenerateInscriptionRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateAttestationInscription($request->student_pending_student_id)); }

    public function generateBulletin(GenerateBulletinRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateBulletin($request->student_pending_student_id, $request->academic_year_id)); }

    public function generateLicence(GenerateAttestationRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateAttestationLicence($request->student_pending_student_id)); }

    public function generateMultiplePassage(GenerateMultiplePassageRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateMultipleAttestationsPassage($request->student_pending_student_ids)); }

    public function generateMultiplePreparatory(GenerateMultiplePreparatoryRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateMultipleCertificatsPreparatoires($request->student_pending_student_ids)); }

    public function generateMultipleDefinitive(GenerateMultipleDefinitiveRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateMultipleAttestationsDefinitive($request->student_pending_student_ids)); }

    public function generateMultipleInscription(GenerateMultipleInscriptionRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateMultipleAttestationsInscription($request->student_pending_student_ids)); }

    public function generateMultipleBulletins(GenerateMultipleBulletinsRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateMultipleBulletins($request->bulletins)); }

    public function generateMultipleLicence(GenerateMultipleLicenceRequest $request)
    { return $this->tryGenerate(fn () => $this->attestationService->generateMultipleAttestationsLicence($request->student_pending_student_ids)); }

    public function updateStudentNames(UpdateStudentNamesRequest $request, int $studentPendingStudentId): JsonResponse
    {
        try {
            $sps = StudentPendingStudent::with('pendingStudent.personalInformation')->findOrFail($studentPendingStudentId);
            $pi  = $sps->pendingStudent->personalInformation;
            if (!$pi) return $this->errorResponse('Informations personnelles introuvables', 404);
            $pi->update(['last_name' => $request->last_name, 'first_names' => $request->first_names]);
            return $this->successResponse(['last_name' => $pi->last_name, 'first_names' => $pi->first_names], 'Noms mis à jour avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getBirthCertificate(int $studentPendingStudentId): JsonResponse
    {
        try {
            $sps  = StudentPendingStudent::with('pendingStudent')->findOrFail($studentPendingStudentId);
            $file = $sps->pendingStudent->files()->where(fn ($q) =>
                $q->where('collection', 'birth_certificate')
                  ->orWhere('collection', 'acte_naissance')
                  ->orWhere('original_name', 'like', '%acte%naissance%')
                  ->orWhere('original_name', 'like', '%birth%certificate%')
            )->first();
            if (!$file) return $this->errorResponse('Acte de naissance introuvable', 404);
            return $this->successResponse(['url' => $file->url ?? null, 'path' => $file->path ?? null], 'Acte de naissance récupéré');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPER PRIVÉ — B1.5 DRY : élimine 13 blocs try/catch identiques
    // ══════════════════════════════════════════════════════════════════════════

    private function tryGenerate(callable $fn)
    {
        try {
            return $fn();
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

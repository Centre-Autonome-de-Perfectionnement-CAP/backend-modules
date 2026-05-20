<?php

namespace App\Modules\Attestation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attestation\Services\AttestationService;
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
use App\Modules\Inscription\Models\{StudentPendingStudent, Student, AcademicYear, AcademicPath};
use App\Modules\Finance\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Module Attestation — Controller pour le progiciel interne
 *
 * Routes publiques (storeDemande, storeBulletinDemande, suiviDemande,
 * generateAndSendQuittance) → déplacées vers Public\DemandeController
 * et Public\QuittanceController.
 *
 * Ce controller ne conserve que :
 *   - les endpoints publics de consultation (getStatus, getBulletinStatus, identify, checkAvailability)
 *   - les routes protégées d'éligibilité et de génération (progiciel)
 */
class AttestationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AttestationService $attestationService,
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
        $student = Student::where('student_id_number', strtoupper(trim($request->matricule)))->first();
        if (!$student) return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);

        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn($q) => $q->where('status', 'approved'))
            ->with(['pendingStudent.personalInformation', 'pendingStudent.department.cycle', 'pendingStudent.academicYear'])
            ->latest('id')->first();
        if (!$link) return response()->json(['message' => 'Aucune inscription approuvée trouvée.'], 404);

        $pending  = $link->pendingStudent;
        $personal = $pending->personalInformation;
        $existingRequests = DB::table('document_requests')
            ->where('student_pending_student_id', $link->id)
            ->orderBy('submitted_at', 'desc')->get()->keyBy('type');

        $path       = AcademicPath::where('student_pending_student_id', $link->id)->latest('id')->first();
        $cycle      = $pending->department?->cycle;
        $yearsCount = (int)($cycle?->years_count ?? 0);
        $rawLevel   = $path?->study_level ?? 0;
        $studyLevel = is_numeric($rawLevel) ? (int)$rawLevel : (int)preg_replace('/^[A-Za-z]+/', '', (string)$rawLevel);
        $hasPass    = $path && $path->year_decision === 'pass' && !empty($path->deliberation_date);
        $hasPayment = DB::table('payments')->where('student_pending_student_id', $link->id)->where('status', 'approved')->whereNull('deleted_at')->exists();
        $isApproved = $pending->status === 'approved';

        $types = [
            'attestation_passage'     => $isApproved && $hasPayment && $hasPass && $yearsCount > 0 && $studyLevel < $yearsCount,
            'attestation_definitive'  => $isApproved && $hasPayment && $hasPass && $yearsCount > 0 && $studyLevel >= $yearsCount,
            'attestation_inscription' => $isApproved && $hasPayment,
        ];

        $documents = [];
        foreach ($types as $type => $eligible) {
            $existing = $existingRequests->get($type);
            if ($existing) {
                $documents[] = ['type' => $type, 'status' => $existing->status, 'reference' => $existing->reference, 'submittedAt' => $existing->submitted_at, 'rejectedReason' => $existing->rejected_reason ?? null];
            } elseif ($eligible) {
                $documents[] = ['type' => $type, 'status' => 'disponible'];
            }
        }

        return response()->json(['success' => true, 'data' => [
            'student'   => ['last_name' => $personal->last_name, 'first_names' => $personal->first_names, 'matricule' => $student->student_id_number, 'level' => $pending->level ?? '—', 'department' => $pending->department?->name ?? '—', 'academic_year' => $pending->academicYear?->academic_year ?? '—'],
            'documents' => $documents,
        ]]);
    }

    public function getBulletinStatus(Request $request): JsonResponse
    {
        $request->validate(['matricule' => 'required|string']);
        $student = Student::where('student_id_number', strtoupper(trim($request->matricule)))->first();
        if (!$student) return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);

        $links = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn($q) => $q->where('status', 'approved'))
            ->with(['pendingStudent.personalInformation', 'pendingStudent.department', 'pendingStudent.academicYear'])
            ->get();
        if ($links->isEmpty()) return response()->json(['message' => 'Aucune inscription approuvée trouvée.'], 404);

        $latest   = $links->sortByDesc('id')->first();
        $personal = $latest->pendingStudent->personalInformation;
        $semestres = ['s1', 's2', 's3', 's4', 's5', 's6', 's7', 's8'];
        $years = [];

        foreach ($links as $link) {
            $year = $link->pendingStudent->academicYear;
            if (!$year) continue;
            $hasPaid = DB::table('payments')->where('student_pending_student_id', $link->id)->where('status', 'approved')->whereNull('deleted_at')->exists();
            if (!$hasPaid) continue;
            $hasPath = AcademicPath::where('student_pending_student_id', $link->id)->whereNotNull('study_level')->exists();
            if (!$hasPath) continue;
            $existing = DB::table('document_requests')->where('student_pending_student_id', $link->id)->where('type', 'like', 'bulletin_%')->orderBy('submitted_at', 'desc')->get()->keyBy('type');
            $semestresData = [];
            foreach ($semestres as $s) {
                $type = "bulletin_{$s}";
                $req  = $existing->get($type);
                $semestresData[] = $req
                    ? ['semestre' => strtoupper($s), 'type' => $type, 'status' => $req->status, 'reference' => $req->reference, 'submittedAt' => $req->submitted_at, 'rejectedReason' => $req->rejected_reason ?? null]
                    : ['semestre' => strtoupper($s), 'type' => $type, 'status' => 'disponible'];
            }
            $years[] = ['link_id' => $link->id, 'academic_year' => $year->academic_year, 'year_id' => $year->id, 'is_current' => (bool)$year->is_current, 'semestres' => $semestresData];
        }
        usort($years, fn($a, $b) => strcmp($b['academic_year'], $a['academic_year']));

        return response()->json(['success' => true, 'data' => [
            'student' => ['last_name' => $personal->last_name, 'first_names' => $personal->first_names, 'matricule' => $student->student_id_number, 'level' => $latest->pendingStudent->level ?? '—', 'department' => $latest->pendingStudent->department?->name ?? '—'],
            'years'   => $years,
        ]]);
    }

    public function identify(Request $request): JsonResponse
    {
        $request->validate(['matricule' => 'required|string', 'academic_year' => 'required|string']);
        $student = Student::where('student_id_number', strtoupper(trim($request->matricule)))->first();
        if (!$student) return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);
        $year = AcademicYear::where('academic_year', $request->academic_year)->first();
        if (!$year) return response()->json(['message' => 'Année académique introuvable.'], 404);
        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn($q) => $q->where('academic_year_id', $year->id)->where('status', 'approved'))
            ->with(['pendingStudent.personalInformation', 'pendingStudent.department'])->first();
        if (!$link) return response()->json(['message' => 'Aucune inscription approuvée trouvée pour ce matricule et cette année académique.'], 404);
        $personal = $link->pendingStudent->personalInformation;
        return response()->json(['success' => true, 'data' => ['last_name' => $personal->last_name, 'first_names' => $personal->first_names, 'matricule' => $student->student_id_number, 'level' => $link->pendingStudent->level ?? '—', 'department' => $link->pendingStudent->department?->name ?? '—', 'academic_year' => $year->academic_year]]);
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate(['matricule' => 'required|string', 'academic_year' => 'required|string', 'type' => 'required|in:inscription,passage,definitive']);
        $student = Student::where('student_id_number', strtoupper(trim($request->matricule)))->first();
        $year    = AcademicYear::where('academic_year', $request->academic_year)->first();
        if (!$student || !$year) return $this->unavailable('Données introuvables.');
        $link = StudentPendingStudent::where('student_id', $student->id)->whereHas('pendingStudent', fn($q) => $q->where('academic_year_id', $year->id))->with('pendingStudent.department.cycle')->first();
        if (!$link) return $this->unavailable('Aucune inscription trouvée pour cette année académique.');
        $ps = $link->pendingStudent;
        if ($request->type === 'inscription') {
            if ($ps->status !== 'approved') return $this->unavailable("Votre inscription n'est pas encore approuvée.");
            return Transaction::where('pending_student_id', $ps->id)->where('academic_year_id', $year->id)->exists() ? $this->available() : $this->unavailable('Aucun paiement validé trouvé pour cette année académique.');
        }
        $path = AcademicPath::where('student_pending_student_id', $link->id)->where('academic_year_id', $year->id)->first();
        if (!$path) return $this->unavailable('Aucun parcours académique trouvé pour cette année.');
        if ($path->year_decision !== 'pass') return $this->unavailable("La décision de jury n'est pas encore disponible ou n'est pas favorable.");
        if (empty($path->deliberation_date)) return $this->unavailable("La date de délibération n'est pas encore renseignée.");
        $yearsCount = (int)($ps->department?->cycle?->years_count ?? 0);
        $studyLevel = (int)$path->study_level;
        if (!$yearsCount) return $this->unavailable('Impossible de déterminer la durée du cycle.');
        if ($request->type === 'passage') return $studyLevel >= $yearsCount ? $this->unavailable("Vous êtes en dernière année. Une attestation de passage n'est pas applicable.") : $this->available();
        if ($request->type === 'definitive') return $studyLevel < $yearsCount ? $this->unavailable("Vous n'êtes pas encore en dernière année de votre cycle.") : $this->available();
        return $this->unavailable('Type non reconnu.');
    }

    // ── SUPPRIMÉ : suiviDemande(), storeDemande(), storeBulletinDemande(),
    //    generateAndSendQuittance() — déplacés vers Public\DemandeController
    //    et Public\QuittanceController (références courtes ATT-XXXX / BUL-XXXX).

    // ══════════════════════════════════════════════════════════════════════════
    // ROUTES PROTÉGÉES — Éligibilité + Génération
    // ══════════════════════════════════════════════════════════════════════════

    public function getEligibleForPassage(GetEligibleStudentsRequest $request): JsonResponse { $s = $this->attestationService->getEligibleForPassage($request->academic_year_id, $request->department_id, $request->cohort, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }
    public function getEligibleForPreparatory(GetEligiblePreparatoryRequest $request): JsonResponse { $s = $this->attestationService->getEligibleForPreparatoryClass($request->academic_year_id, $request->department_id, $request->cohort, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }
    public function getEligibleForDefinitive(GetEligibleDefinitiveRequest $request): JsonResponse { $s = $this->attestationService->getEligibleForDefinitive($request->academic_year_id, $request->department_id, $request->cohort, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }
    public function getEligibleForInscription(GetEligibleInscriptionRequest $request): JsonResponse { $s = $this->attestationService->getEligibleForInscription($request->academic_year_id, $request->department_id, $request->search); return $this->successResponse(['students' => $s, 'total' => $s->count()], 'OK'); }

    public function generatePassage(GenerateAttestationRequest $request) { try { return $this->attestationService->generateAttestationPassage($request->student_pending_student_id); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generatePreparatory(GenerateAttestationRequest $request) { try { return $this->attestationService->generateCertificatPreparatoire($request->student_pending_student_id); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateDefinitive(GenerateAttestationRequest $request) { try { return $this->attestationService->generateAttestationDefinitive($request->student_pending_student_id); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateInscription(GenerateInscriptionRequest $request) { try { return $this->attestationService->generateAttestationInscription($request->student_pending_student_id); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateBulletin(GenerateBulletinRequest $request) { try { return $this->attestationService->generateBulletin($request->student_pending_student_id, $request->academic_year_id); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateLicence(GenerateAttestationRequest $request) { try { return $this->attestationService->generateAttestationLicence($request->student_pending_student_id); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }

    public function generateMultiplePassage(GenerateMultiplePassageRequest $request) { try { return $this->attestationService->generateMultipleAttestationsPassage($request->student_pending_student_ids); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateMultiplePreparatory(GenerateMultiplePreparatoryRequest $request) { try { return $this->attestationService->generateMultipleCertificatsPreparatoires($request->student_pending_student_ids); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateMultipleDefinitive(GenerateMultipleDefinitiveRequest $request) { try { return $this->attestationService->generateMultipleAttestationsDefinitive($request->student_pending_student_ids); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateMultipleInscription(GenerateMultipleInscriptionRequest $request) { try { return $this->attestationService->generateMultipleAttestationsInscription($request->student_pending_student_ids); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateMultipleBulletins(GenerateMultipleBulletinsRequest $request) { try { return $this->attestationService->generateMultipleBulletins($request->bulletins); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }
    public function generateMultipleLicence(GenerateMultipleLicenceRequest $request) { try { return $this->attestationService->generateMultipleAttestationsLicence($request->student_pending_student_ids); } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); } }

    public function updateStudentNames(UpdateStudentNamesRequest $request, int $studentPendingStudentId): JsonResponse
    {
        try {
            $sps = StudentPendingStudent::with('pendingStudent.personalInformation')->findOrFail($studentPendingStudentId);
            $pi  = $sps->pendingStudent->personalInformation;
            if (!$pi) return $this->errorResponse('Informations personnelles introuvables', 404);
            $pi->update(['last_name' => $request->last_name, 'first_names' => $request->first_names]);
            return $this->successResponse(['last_name' => $pi->last_name, 'first_names' => $pi->first_names], 'Noms mis à jour avec succès');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); }
    }

    public function getBirthCertificate(int $studentPendingStudentId): JsonResponse
    {
        try {
            $sps  = StudentPendingStudent::with('pendingStudent')->findOrFail($studentPendingStudentId);
            $file = $sps->pendingStudent->files()->where(fn($q) => $q->where('collection', 'birth_certificate')->orWhere('collection', 'acte_naissance')->orWhere('original_name', 'like', '%acte%naissance%')->orWhere('original_name', 'like', '%birth%certificate%'))->first();
            if (!$file) return $this->errorResponse('Acte de naissance introuvable', 404);
            return $this->successResponse(['url' => $file->url ?? null, 'path' => $file->path ?? null], 'Acte de naissance récupéré');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage(), 500); }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════════════

    private function available(): JsonResponse { return response()->json(['success' => true, 'data' => ['available' => true]]); }
    private function unavailable(string $r): JsonResponse { return response()->json(['success' => true, 'data' => ['available' => false, 'reason' => $r]]); }
}

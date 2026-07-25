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
use Illuminate\Support\Facades\{DB, Log, Mail, Storage};
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Module Attestation — Controller complet pour proj2
 *
 * Corrections vs version précédente de proj2 :
 *   ✅ generateAndSendQuittance() ajouté (manquait, causait erreur 404 côté site vitrine)
 *   ✅ getEligibleForInscription() → appelle AttestationService::getEligibleForInscription()
 *   ✅ generateInscription(), generateDefinitive(), generatePassage() → service complet
 *   ✅ generateMultiple* → toutes les versions implémentées
 *
 * Routes document-requests → App\Modules\Demandes\routes\api.php
 */
class AttestationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AttestationService $attestationService) {}

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

    public function suiviDemande(Request $request): JsonResponse
    {
        $request->validate(['reference' => 'required|string']);
        $demande = DB::table('document_requests')->where('reference', strtoupper(trim($request->reference)))->first();
        if (!$demande) return response()->json(['message' => 'Aucune demande trouvée avec cette référence.'], 404);
        $link = StudentPendingStudent::with(['student', 'pendingStudent.personalInformation', 'pendingStudent.department', 'pendingStudent.academicYear'])->find($demande->student_pending_student_id);
        $p    = $link?->pendingStudent?->personalInformation;
        return response()->json(['success' => true, 'data' => [
            'reference' => $demande->reference, 'type' => $demande->type, 'status' => $demande->status,
            'submitted_at' => $demande->submitted_at, 'rejected_reason' => $demande->rejected_reason ?? null,
            'student' => ['last_name' => $p?->last_name ?? '—', 'first_names' => $p?->first_names ?? '—', 'matricule' => $link?->student?->student_id_number ?? '—', 'level' => $link?->pendingStudent?->level ?? '—', 'department' => $link?->pendingStudent?->department?->name ?? '—', 'academic_year' => $link?->pendingStudent?->academicYear?->academic_year ?? '—'],
        ]]);
    }

    public function storeDemande(Request $request): JsonResponse
    {
        $request->validate(['matricule' => 'required|string', 'type' => 'required|string', 'email' => 'required|email', 'payment_method' => 'nullable|in:manual,tresor_online', 'payment_reference' => 'nullable|string|max:50']);
        $student = Student::where('student_id_number', strtoupper(trim($request->matricule)))->first();
        if (!$student) return response()->json(['message' => 'Étudiant introuvable.'], 404);
        $link = StudentPendingStudent::where('student_id', $student->id)->whereHas('pendingStudent', fn($q) => $q->where('status', 'approved'))->with(['pendingStudent.academicYear', 'pendingStudent.personalInformation'])->latest('id')->first();
        if (!$link) return response()->json(['message' => 'Inscription approuvée introuvable.'], 404);
        $validTypes = ['attestation_passage', 'attestation_definitive', 'attestation_inscription'];
        if (!in_array($request->type, $validTypes)) return response()->json(['message' => "Type d'attestation invalide."], 422);
        if ($this->hasActiveDemande($link->id, $request->type)) return response()->json(['message' => "Vous avez déjà une demande en cours pour ce type d'attestation."], 422);

        $typeToFolder = ['attestation_definitive' => 'definitive', 'attestation_inscription' => 'inscription', 'attestation_passage' => 'passage'];
        $filesByType  = ['attestation_definitive' => ['demande_manuscrite', 'acte_naissance', 'attestation_succes_file', 'quittance'], 'attestation_inscription' => ['demande_manuscrite', 'recu_paiement', 'quittance'], 'attestation_passage' => ['demande_manuscrite', 'acte_naissance', 'recu_paiement', 'bulletin', 'quittance']];
        $typeLabels   = ['attestation_passage' => 'Attestation de Passage', 'attestation_definitive' => 'Attestation Définitive', 'attestation_inscription' => "Attestation d'Inscription"];
        $montantMap   = ['attestation_passage' => 2000, 'attestation_definitive' => 2000, 'attestation_inscription' => 2000];

        $reference = 'ATT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $subFolder = $typeToFolder[$request->type] ?? 'autre';
        $pm = $request->input('payment_method', 'manual');
        $pr = $request->input('payment_reference');

        $files = $this->storeFiles($request, $filesByType[$request->type] ?? [], "attestation-demandes/{$subFolder}/{$reference}");
        DB::table('document_requests')->insert(['reference' => $reference, 'student_pending_student_id' => $link->id, 'academic_year_id' => $link->pendingStudent->academic_year_id ?? null, 'type' => $request->type, 'status' => 'pending', 'email' => $request->email, 'payment_method' => $pm, 'payment_reference' => $pr, 'files' => !empty($files) ? json_encode($files) : null, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        [$pdf, $pdfFile] = $this->maybeGenerateQuittancePdf($pm, $pr, $link, $student, $reference, ($typeLabels[$request->type] ?? $request->type) . ' — CAP-EPAC', $montantMap[$request->type] ?? 2000);

        $quittancePdfUrl = null;
        if ($pdf) {
            try {
                $qPath = "attestation-demandes/{$subFolder}/{$reference}/{$pdfFile}";
                Storage::disk('public')->put($qPath, $pdf);
                $files['quittance_generee'] = $qPath;
                DB::table('document_requests')->where('reference', $reference)->update(['files' => json_encode($files)]);
                $quittancePdfUrl = Storage::disk('public')->url($qPath);
            } catch (\Exception $e) { Log::warning('Sauvegarde PDF quittance échouée : ' . $e->getMessage()); }
        }

        $this->sendConfirmationEmail('core::emails.attestation-confirmation', $request->email, "Demande d'attestation reçue — Réf : {$reference}", ['reference' => $reference, 'type' => $request->type, 'studentName' => $student->student_id_number, 'submittedAt' => now()->format('d/m/Y à H:i'), 'paymentMethod' => $pm, 'paymentRef' => $pr], $pdf, $pdfFile);

        return response()->json(['success' => true, 'data' => ['message' => 'Demande enregistrée avec succès. Un email de confirmation vous a été envoyé.', 'reference' => $reference, 'payment_method' => $pm, 'payment_reference' => $pr, 'quittance_pdf_url' => $quittancePdfUrl, 'quittance_pdf_base64' => $pdf ? base64_encode($pdf) : null, 'quittance_filename' => $pdfFile]], 201);
    }

    public function storeBulletinDemande(Request $request): JsonResponse
    {
        $request->validate(['link_id' => 'required|integer', 'type' => 'required|string', 'email' => 'required|email', 'payment_method' => 'nullable|in:manual,tresor_online', 'payment_reference' => 'nullable|string|max:50']);
        $link = StudentPendingStudent::with(['pendingStudent.academicYear', 'pendingStudent.personalInformation'])->find($request->link_id);
        if (!$link) return response()->json(['message' => 'Inscription introuvable.'], 404);
        if ($this->hasActiveDemande($link->id, $request->type)) return response()->json(['message' => 'Vous avez déjà une demande en cours pour ce bulletin.'], 422);
        $year = $link->pendingStudent->academicYear;
        $reference = 'BUL-' . strtoupper(substr(uniqid(), -8));
        $pm = $request->input('payment_method', 'manual');
        $pr = $request->input('payment_reference');
        $files = $this->storeFiles($request, ['demande_manuscrite', 'acte_naissance', 'quittance'], "bulletins-demandes/{$reference}");
        DB::table('document_requests')->insert(['reference' => $reference, 'student_pending_student_id' => $link->id, 'academic_year_id' => $year?->id, 'type' => $request->type, 'status' => 'pending', 'email' => $request->email, 'payment_method' => $pm, 'payment_reference' => $pr, 'files' => !empty($files) ? json_encode($files) : null, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        [$pdf, $pdfFile] = $this->maybeGenerateQuittancePdf($pm, $pr, $link, $link->student, $reference, 'Bulletin de notes — CAP-EPAC', 500);
        $this->sendConfirmationEmail('core::emails.bulletin-confirmation', $request->email, "Demande de bulletin reçue — Réf : {$reference}", ['reference' => $reference, 'type' => $request->type, 'academicYear' => $year?->academic_year ?? '—', 'submittedAt' => now()->format('d/m/Y à H:i'), 'paymentMethod' => $pm, 'paymentRef' => $pr], $pdf, $pdfFile);
        return response()->json(['success' => true, 'data' => ['message' => 'Demande de bulletin enregistrée avec succès. Un email de confirmation vous a été envoyé.', 'reference' => $reference, 'payment_method' => $pm, 'payment_reference' => $pr]], 201);
    }

    /**
     * POST /api/attestations/quittance/generate
     * Génère PDF quittance + envoi email. Manquait dans proj2.
     */
    public function generateAndSendQuittance(Request $request): JsonResponse
    {
        $request->validate(['quittanceNumber' => 'required|string', 'montant' => 'required|integer|min:1', 'motif' => 'required|string', 'nomEtudiant' => 'required|string', 'matricule' => 'required|string', 'email' => 'required|email', 'referenceDemande' => 'required|string', 'paidAt' => 'required|string', 'simulation' => 'boolean']);
        try {
            $datePaiement = Carbon::parse($request->paidAt)->setTimezone('Africa/Porto-Novo')->translatedFormat('d F Y à H\hi');
            $pdfContent = Pdf::loadView('core::pdfs.quittance-tresor', ['quittanceNumber' => $request->quittanceNumber, 'montant' => $request->montant, 'motif' => $request->motif, 'nomEtudiant' => strtoupper($request->nomEtudiant), 'matricule' => strtoupper($request->matricule), 'referenceDemande' => strtoupper($request->referenceDemande), 'datePaiement' => $datePaiement, 'simulation' => $request->boolean('simulation', true)])->setPaper('A4', 'portrait')->output();
            $pdfFilename = 'quittance-' . $request->quittanceNumber . '.pdf';
            Mail::send('core::emails.attestation-confirmation', ['reference' => $request->referenceDemande, 'type' => 'paiement_tresor', 'studentName' => $request->matricule, 'submittedAt' => $datePaiement, 'paymentMethod' => 'tresor_online', 'paymentRef' => $request->quittanceNumber], fn($m) => $m->to($request->email)->subject("Votre quittance de paiement — {$request->quittanceNumber}")->attachData($pdfContent, $pdfFilename, ['mime' => 'application/pdf']));
            return response()->json(['success' => true, 'data' => ['message' => 'Quittance générée et envoyée par email.', 'pdfBase64' => base64_encode($pdfContent), 'pdfFilename' => $pdfFilename, 'quittanceNumber' => $request->quittanceNumber]]);
        } catch (\Exception $e) {
            Log::error('Erreur génération quittance PDF : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur lors de la génération de la quittance.'], 500);
        }
    }

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

    private function hasActiveDemande(int $linkId, string $type): bool
    {
        return DB::table('document_requests')->where('student_pending_student_id', $linkId)->where('type', $type)->whereIn('status', ['pending', 'processing', 'ready'])->exists();
    }

    private function storeFiles(Request $request, array $keys, string $folder): array
    {
        $uploaded = [];
        foreach ($keys as $key) {
            if ($request->hasFile($key) && $request->file($key)->isValid()) {
                $file = $request->file($key);
                $ext  = $file->getClientOriginalExtension();
                $uploaded[$key] = $file->storeAs($folder, $key . ($ext ? ".{$ext}" : ''), 'public');
            }
        }
        return $uploaded;
    }

    private function maybeGenerateQuittancePdf(string $pm, ?string $pr, StudentPendingStudent $link, $student, string $reference, string $motif, int $montant): array
    {
        if ($pm !== 'tresor_online' || !$pr) return [null, null];
        try {
            $personal = $link->pendingStudent->personalInformation;
            $nom = strtoupper(trim(($personal->last_name ?? '') . ' ' . ($personal->first_names ?? '')));
            $date = Carbon::now()->setTimezone('Africa/Porto-Novo')->translatedFormat('d F Y à H\hi');
            $pdf = Pdf::loadView('core::pdfs.quittance-tresor', ['quittanceNumber' => $pr, 'montant' => $montant, 'motif' => $motif, 'nomEtudiant' => $nom, 'matricule' => strtoupper($student?->student_id_number ?? ''), 'referenceDemande' => $reference, 'datePaiement' => $date, 'simulation' => true])->setPaper('A4', 'portrait');
            return [$pdf->output(), 'quittance-' . $pr . '.pdf'];
        } catch (\Exception $e) { Log::warning('Génération PDF quittance échouée : ' . $e->getMessage()); return [null, null]; }
    }

    private function sendConfirmationEmail(string $view, string $to, string $subject, array $vars, ?string $pdfContent, ?string $pdfFilename): void
    {
        try {
            Mail::send($view, $vars, function ($m) use ($to, $subject, $pdfContent, $pdfFilename) {
                $m->to($to)->subject($subject);
                if ($pdfContent && $pdfFilename) $m->attachData($pdfContent, $pdfFilename, ['mime' => 'application/pdf']);
            });
        } catch (\Exception $e) { Log::error('Échec envoi email : ' . $e->getMessage()); }
    }
}

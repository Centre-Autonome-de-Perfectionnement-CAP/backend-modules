<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Services\NotificationService;
use App\Modules\Demandes\Services\WhatsAppService;
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Storage};
use Illuminate\Support\Str;

/**
 * Soumission des demandes de documents (site vitrine — accès public)
 *
 * POST /api/attestations/demandes  → storeDemande      (attestations)
 * POST /api/attestations/bulletins → storeBulletinDemande (bulletins)
 * GET  /api/attestations/demandes/suivi
 *
 * WhatsApp est obligatoire à la soumission.
 * Le numéro est normalisé côté backend (WhatsAppService::normalizePhone)
 * avant d'être stocké — le frontend envoie le numéro brut, le backend
 * garantit le format E.164 en base.
 *
 * Notifications déclenchées automatiquement :
 *  → Étudiant   : email + WhatsApp (accusé de réception)
 *  → Secrétaire : email + WhatsApp (nouveau dossier à traiter)
 */
class DemandeController extends Controller
{
    // ── Constantes ────────────────────────────────────────────────────────────

    private const ATTESTATION_TYPES = [
        'attestation_passage',
        'attestation_definitive',
        'attestation_inscription',
    ];

    private const TYPE_TO_FOLDER = [
        'attestation_definitive'  => 'definitive',
        'attestation_inscription' => 'inscription',
        'attestation_passage'     => 'passage',
    ];

    private const ATTESTATION_FILES = [
        'attestation_definitive'  => ['demande_manuscrite', 'acte_naissance', 'attestation_succes_file', 'quittance'],
        'attestation_inscription' => ['demande_manuscrite', 'recu_paiement', 'quittance'],
        'attestation_passage'     => ['demande_manuscrite', 'acte_naissance', 'recu_paiement', 'bulletin', 'quittance'],
    ];

    private const TYPE_LABELS = [
        'attestation_passage'     => 'Attestation de Passage',
        'attestation_definitive'  => 'Attestation Définitive',
        'attestation_inscription' => "Attestation d'Inscription",
    ];

    private const MONTANTS_ATTESTATION = [
        'attestation_passage'     => 2000,
        'attestation_definitive'  => 2000,
        'attestation_inscription' => 2000,
    ];

    public function __construct(
        protected NotificationService $notificationService,
        protected WhatsAppService     $whatsAppService,
    ) {}

    // ── POST /api/attestations/demandes ───────────────────────────────────────

    public function storeDemande(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'         => 'required|string',
            'type'              => 'required|string',
            'email'             => 'required|email',
            'whatsapp'          => 'required|string|max:30',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50',
        ]);

        // Normaliser le numéro WhatsApp
        $whatsappNormalized = $this->whatsAppService->normalizePhone($request->whatsapp);
        if (!$whatsappNormalized) {
            return response()->json([
                'message' => 'Numéro WhatsApp invalide. Formats acceptés : 97123456, 0197123456, +22997123456.',
                'errors'  => ['whatsapp' => ['Numéro WhatsApp invalide.']],
            ], 422);
        }

        // Étudiant
        $student = Student::where('student_id_number', strtoupper(trim($request->matricule)))->first();
        if (!$student) {
            return response()->json(['message' => 'Étudiant introuvable.'], 404);
        }

        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn($q) => $q->where('status', 'approved'))
            ->with(['pendingStudent.academicYear', 'pendingStudent.personalInformation'])
            ->latest('id')
            ->first();

        if (!$link) {
            return response()->json(['message' => 'Inscription approuvée introuvable.'], 404);
        }

        if (!in_array($request->type, self::ATTESTATION_TYPES)) {
            return response()->json(['message' => "Type d'attestation invalide."], 422);
        }

        // Doublon actif
        $existing = DB::table('document_requests')
            ->where('student_pending_student_id', $link->id)
            ->where('type', $request->type)
            ->whereNotIn('status', ['rejected', 'delivered'])
            ->first();

        if ($existing) {
            return response()->json([
                'message'   => 'Une demande active existe déjà pour ce type.',
                'reference' => $existing->reference,
            ], 409);
        }

        // Upload fichiers
        $folder    = self::TYPE_TO_FOLDER[$request->type] ?? 'divers';
        $fileKeys  = self::ATTESTATION_FILES[$request->type] ?? [];
        $filesData = [];

        foreach ($fileKeys as $key) {
            if ($request->hasFile($key)) {
                $path            = $request->file($key)->store("demandes/{$folder}/{$key}", 'public');
                $filesData[$key] = $path;
            }
        }

        // Référence
        $reference = 'ATT-' . Str::upper(Str::random(4));

        // Paiement + quittance PDF
        $paymentMethod    = $request->payment_method ?? 'manual';
        $paymentReference = $request->payment_reference;
        $montant          = self::MONTANTS_ATTESTATION[$request->type] ?? 2000;
        $quittancePath    = null;

        if ($paymentMethod === 'tresor_online' && $paymentReference) {
            try {
                $pi  = $link->pendingStudent->personalInformation;
                $nom = trim(($pi->last_name ?? '') . ' ' . ($pi->first_names ?? ''));
                $pdf = Pdf::loadView('core::pdf.quittance', [
                    'reference'        => $reference,
                    'matricule'        => $student->student_id_number,
                    'nomEtudiant'      => $nom,
                    'typeDocument'     => self::TYPE_LABELS[$request->type],
                    'montant'          => number_format($montant, 0, ',', ' ') . ' FCFA',
                    'paymentReference' => $paymentReference,
                    'date'             => now()->format('d/m/Y à H:i'),
                ]);
                $quittancePath = "demandes/quittances/{$reference}.pdf";
                Storage::disk('public')->put($quittancePath, $pdf->output());
            } catch (\Exception $e) {
                Log::error('[DemandeController] Erreur génération quittance', ['error' => $e->getMessage()]);
            }
        }

        if ($quittancePath) {
            $filesData['quittance_online'] = $quittancePath;
        }

        // INSERT
        $id = DB::table('document_requests')->insertGetId([
            'student_pending_student_id' => $link->id,
            'type'                       => $request->type,
            'reference'                  => $reference,
            'status'                     => 'pending',
            'email'                      => $request->email,
            'demandeur_whatsapp'         => $whatsappNormalized,  // ← format E.164 garanti
            'payment_method'             => $paymentMethod,
            'payment_reference'          => $paymentReference,
            'files'                      => json_encode($filesData),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $demande = DB::table('document_requests')->where('id', $id)->first();

        // ── Notifications (étudiant + secrétaire) ─────────────────────────────
        // sendSoumission() gère les deux côtés en une seule méthode.
        $this->notificationService->sendSoumission($demande);

        return response()->json([
            'message'   => 'Demande soumise avec succès.',
            'reference' => $reference,
        ], 201);
    }

    // ── POST /api/attestations/bulletins ──────────────────────────────────────

    public function storeBulletinDemande(Request $request): JsonResponse
    {
        $request->validate([
            'link_id'           => 'required|integer|exists:student_pending_student,id',
            'type'              => 'required|string|starts_with:bulletin_',
            'email'             => 'required|email',
            'whatsapp'          => 'required|string|max:30',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50',
        ]);

        // Normaliser le numéro WhatsApp
        $whatsappNormalized = $this->whatsAppService->normalizePhone($request->whatsapp);
        if (!$whatsappNormalized) {
            return response()->json([
                'message' => 'Numéro WhatsApp invalide. Formats acceptés : 97123456, 0197123456, +22997123456.',
                'errors'  => ['whatsapp' => ['Numéro WhatsApp invalide.']],
            ], 422);
        }

        $link = StudentPendingStudent::with(['pendingStudent', 'pendingStudent.academicYear'])
            ->find($request->link_id);

        if (!$link || $link->pendingStudent?->status !== 'approved') {
            return response()->json(['message' => 'Inscription introuvable ou non approuvée.'], 404);
        }

        // Doublon actif
        $existing = DB::table('document_requests')
            ->where('student_pending_student_id', $link->id)
            ->where('type', $request->type)
            ->whereNotIn('status', ['rejected', 'delivered'])
            ->first();

        if ($existing) {
            return response()->json([
                'message'   => 'Une demande active existe déjà pour ce bulletin.',
                'reference' => $existing->reference,
            ], 409);
        }

        // Upload fichiers
        $filesData = [];
        foreach (['demande_manuscrite', 'acte_naissance', 'quittance'] as $key) {
            if ($request->hasFile($key)) {
                $path            = $request->file($key)->store("demandes/bulletins/{$key}", 'public');
                $filesData[$key] = $path;
            }
        }

        $reference     = 'BUL-' . Str::upper(Str::random(4));
        $paymentMethod = $request->payment_method ?? 'manual';

        // INSERT
        $id = DB::table('document_requests')->insertGetId([
            'student_pending_student_id' => $link->id,
            'type'                       => $request->type,
            'reference'                  => $reference,
            'status'                     => 'pending',
            'email'                      => $request->email,
            'demandeur_whatsapp'         => $whatsappNormalized,  // ← format E.164 garanti
            'payment_method'             => $paymentMethod,
            'payment_reference'          => $request->payment_reference ?? null,
            'files'                      => json_encode($filesData),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $demande = DB::table('document_requests')->where('id', $id)->first();

        // ── Notifications (étudiant + secrétaire) ─────────────────────────────
        $this->notificationService->sendSoumission($demande);

        return response()->json([
            'message'   => 'Demande de bulletin soumise avec succès.',
            'reference' => $reference,
        ], 201);
    }

    // ── GET /api/attestations/demandes/suivi ──────────────────────────────────

    public function suiviDemande(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $demande = DB::table('document_requests')
            ->where('reference', strtoupper(trim($request->reference)))
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        $statusPublic = $demande->status;

        // Récupérer le motif de la réserve si le dossier est sous réserve
        $flagReason = null;
        if (!empty($demande->has_flag)) {
            $history = DB::table('document_request_histories')
                ->where('document_request_id', $demande->id)
                ->where('action', 'like', '%_flagged')
                ->latest('id')
                ->first();
            $flagReason = $history ? $history->comment : 'Dossier validé sous réserve.';
        }

        // Récupérer les infos de l'étudiant pour l'affichage public
        $studentData = DB::table('student_pending_student')
            ->join('students', 'student_pending_student.student_id', '=', 'students.id')
            ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->where('student_pending_student.id', $demande->student_pending_student_id)
            ->select('personal_information.last_name', 'personal_information.first_names', 'students.student_id_number as matricule')
            ->first();

        return response()->json([
            'reference'       => $demande->reference,
            'type'            => $demande->type,
            'status'          => $statusPublic,
            'has_flag'        => !empty($demande->has_flag),
            'flag_reason'     => $flagReason,
            'submitted_at'    => $demande->created_at,
            'rejected_reason' => $demande->status === 'rejected' ? $demande->rejected_reason : null,
            'student'         => $studentData ? [
                'last_name'   => $studentData->last_name,
                'first_names' => $studentData->first_names,
                'matricule'   => $studentData->matricule,
            ] : null,
        ]);
    }
}

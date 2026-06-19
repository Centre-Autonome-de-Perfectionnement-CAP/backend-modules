<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Services\DocumentStorageService;
use App\Modules\Demandes\Services\NotificationService;
use App\Modules\Demandes\Services\WhatsAppService;
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;

/**
 * Soumission des demandes de documents (site vitrine — accès public)
 *
 * POST /api/attestations/demandes  → storeDemande      (attestations)
 * POST /api/attestations/bulletins → storeBulletinDemande (bulletins)
 * GET  /api/attestations/demandes/suivi
 *
 * Structure de stockage (via DocumentStorageService) :
 *   storage/app/public/demandes/{type-slug}/{reference}/
 *     ├── demande/       ← fichiers initiaux
 *     ├── complement/    ← compléments (jamais écrasés)
 *     └── secretaire/    ← fichiers ajoutés par la secrétaire
 *
 * Vérification doublon actif :
 *   Une demande est considérée active si son statut n'est ni 'rejected' ni 'picked_up'.
 *   (picked_up = statut terminal de délivrance, rejected = seul cas permettant resoumission)
 */
class DemandeController extends Controller
{
    // ── Types d'attestations acceptés ─────────────────────────────────────────

    private const ATTESTATION_TYPES = [
        'attestation_passage',
        'attestation_definitive',
        'attestation_inscription',
    ];

    // ── Pièces requises par type ──────────────────────────────────────────────

    private const ATTESTATION_FILES = [
        'attestation_definitive'  => ['demande_manuscrite', 'acte_naissance', 'attestation_succes_file', 'quittance'],
        'attestation_inscription' => ['demande_manuscrite', 'recu_paiement', 'quittance'],
        'attestation_passage'     => ['demande_manuscrite', 'acte_naissance', 'recu_paiement', 'bulletin', 'quittance'],
    ];

    // ── Libellés pour la quittance PDF ───────────────────────────────────────

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

    // ── Statuts terminaux — la demande n'est plus considérée "active" ─────────
    // rejected  = seul statut permettant la resoumission
    // picked_up = document remis physiquement à l'étudiant (demande archivée)

    private const INACTIVE_STATUSES = ['rejected', 'picked_up'];

    public function __construct(
        protected NotificationService    $notificationService,
        protected WhatsAppService        $whatsAppService,
        protected DocumentStorageService $storageService,
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // POST /api/attestations/demandes
    // ══════════════════════════════════════════════════════════════════════════

    public function storeDemande(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'         => 'required|string',
            'type'              => 'required|in:attestation_passage,attestation_definitive,attestation_inscription',
            'email'             => 'required|email',
            'whatsapp'          => 'required|string|max:30',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50',
        ]);

        // Normalisation WhatsApp
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

        // Vérification doublon actif
        // Une demande est "active" si son statut n'est ni rejected ni picked_up.
        $existing = DB::table('document_requests')
            ->where('student_pending_student_id', $link->id)
            ->where('type', $request->type)
            ->whereNotIn('status', self::INACTIVE_STATUSES)
            ->first();

        if ($existing) {
            return response()->json([
                'message'   => 'Une demande active existe déjà pour ce type d\'attestation.',
                'reference' => $existing->reference,
                'status'    => $existing->status,
            ], 409);
        }

        $reference = 'ATT-' . Str::upper(Str::random(4));

        // Création de la structure de dossiers normalisée
        $this->storageService->ensureStructure($request->type, $reference);

        // Stockage des fichiers dans demande/
        $fileKeys     = self::ATTESTATION_FILES[$request->type] ?? [];
        $filesToStore = [];
        foreach ($fileKeys as $key) {
            if ($request->hasFile($key)) {
                $filesToStore[$key] = $request->file($key);
            }
        }
        $filesData = $this->storageService->storeDemandeFiles($request->type, $reference, $filesToStore);

        // Quittance Trésor Public en ligne (PDF généré)
        $paymentMethod    = $request->payment_method ?? 'manual';
        $paymentReference = $request->payment_reference;
        $montant          = self::MONTANTS_ATTESTATION[$request->type] ?? 2000;

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
                $quittancePath = $this->storageService->storePdfContent(
                    $request->type,
                    $reference,
                    'quittance-online.pdf',
                    $pdf->output()
                );
                $filesData['quittance_online'] = $quittancePath;
            } catch (\Exception $e) {
                Log::error('[DemandeController] Erreur génération quittance', [
                    'error'     => $e->getMessage(),
                    'reference' => $reference,
                ]);
            }
        }

        // Insertion en base
        $id = DB::table('document_requests')->insertGetId([
            'student_pending_student_id' => $link->id,
            'type'                       => $request->type,
            'reference'                  => $reference,
            'status'                     => 'submitted',
            'email'                      => $request->email,
            'demandeur_whatsapp'         => $whatsappNormalized,
            'payment_method'             => $paymentMethod,
            'payment_reference'          => $paymentReference,
            'files'                      => json_encode($filesData),
            'submitted_at'               => now(),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $demande = DB::table('document_requests')->where('id', $id)->first();

        // Notifications (email + WhatsApp) : ne doivent jamais faire échouer
        // la réponse HTTP. La demande est déjà enregistrée en base à ce stade ;
        // un incident de notification ne doit pas se traduire par un 500 côté client.
        try {
            $this->notificationService->sendSoumission($demande);
        } catch (\Throwable $e) {
            Log::error('[DemandeController] Erreur notification soumission (attestation)', [
                'error'     => $e->getMessage(),
                'reference' => $reference,
            ]);
        }

        return response()->json([
            'message'   => 'Demande soumise avec succès.',
            'reference' => $reference,
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST /api/attestations/bulletins
    // ══════════════════════════════════════════════════════════════════════════

    public function storeBulletinDemande(Request $request): JsonResponse
    {
        $request->validate([
            'link_id'           => 'required|integer|exists:student_pending_student,id',
            'type'              => 'required|in:bulletin_annuel',
            'email'             => 'required|email',
            'whatsapp'          => 'required|string|max:30',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50',
        ]);

        // Normalisation WhatsApp
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

        // Vérification doublon actif
        // Un seul bulletin annuel autorisé par inscription tant qu'il n'est pas
        // rejected (resoumission autorisée) ou picked_up (archive).
        $existing = DB::table('document_requests')
            ->where('student_pending_student_id', $link->id)
            ->where('type', 'bulletin_annuel')
            ->whereNotIn('status', self::INACTIVE_STATUSES)
            ->first();

        if ($existing) {
            return response()->json([
                'message'   => 'Une demande de bulletin est déjà en cours pour cette année académique.',
                'reference' => $existing->reference,
                'status'    => $existing->status,
            ], 409);
        }

        $reference = 'BUL-' . Str::upper(Str::random(4));

        // Création de la structure de dossiers normalisée
        $this->storageService->ensureStructure('bulletin_annuel', $reference);

        // Stockage des fichiers dans demande/
        $filesToStore = [];
        foreach (['demande_manuscrite', 'acte_naissance', 'quittance'] as $key) {
            if ($request->hasFile($key)) {
                $filesToStore[$key] = $request->file($key);
            }
        }
        $filesData = $this->storageService->storeDemandeFiles('bulletin_annuel', $reference, $filesToStore);

        $paymentMethod = $request->payment_method ?? 'manual';

        $id = DB::table('document_requests')->insertGetId([
            'student_pending_student_id' => $link->id,
            'type'                       => 'bulletin_annuel',
            'reference'                  => $reference,
            'status'                     => 'submitted',
            'email'                      => $request->email,
            'demandeur_whatsapp'         => $whatsappNormalized,
            'payment_method'             => $paymentMethod,
            'payment_reference'          => $request->payment_reference ?? null,
            'files'                      => json_encode($filesData),
            'submitted_at'               => now(),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $demande = DB::table('document_requests')->where('id', $id)->first();

        // Notifications (email + WhatsApp) : ne doivent jamais faire échouer
        // la réponse HTTP. La demande est déjà enregistrée en base à ce stade.
        try {
            $this->notificationService->sendSoumission($demande);
        } catch (\Throwable $e) {
            Log::error('[DemandeController] Erreur notification soumission (bulletin)', [
                'error'     => $e->getMessage(),
                'reference' => $reference,
            ]);
        }

        return response()->json([
            'message'   => 'Demande de bulletin soumise avec succès.',
            'reference' => $reference,
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET /api/attestations/demandes/suivi
    // ══════════════════════════════════════════════════════════════════════════

    public function suiviDemande(Request $request): JsonResponse
    {
        $request->validate(['reference' => 'required|string']);

        $demande = DB::table('document_requests')
            ->where('reference', strtoupper(trim($request->reference)))
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        // Motif de réserve (has_flag)
        $flagReason = null;
        if (!empty($demande->has_flag)) {
            $history = DB::table('document_request_histories')
                ->where('document_request_id', $demande->id)
                ->where('action', 'like', '%_flagged')
                ->latest('id')
                ->first();
            $flagReason = $history ? $history->comment : 'Dossier validé sous réserve.';
        }

        // Infos publiques de l'étudiant
        $studentData = DB::table('student_pending_student')
            ->join('students',             'student_pending_student.student_id',       '=', 'students.id')
            ->join('pending_students',     'student_pending_student.pending_student_id', '=', 'pending_students.id')
            ->join('personal_information', 'pending_students.personal_information_id',  '=', 'personal_information.id')
            ->where('student_pending_student.id', $demande->student_pending_student_id)
            ->select(
                'personal_information.last_name',
                'personal_information.first_names',
                'students.student_id_number as matricule'
            )
            ->first();

        return response()->json([
            'reference'       => $demande->reference,
            'type'            => $demande->type,
            'status'          => $demande->status,
            'has_flag'        => !empty($demande->has_flag),
            'flag_reason'     => $flagReason,
            'submitted_at'    => $demande->submitted_at,
            // Motif de rejet exposé uniquement si le statut est rejected
            'rejected_reason' => $demande->status === 'rejected' ? $demande->rejected_reason : null,
            'student'         => $studentData ? [
                'last_name'   => $studentData->last_name,
                'first_names' => $studentData->first_names,
                'matricule'   => $studentData->matricule,
            ] : null,
        ]);
    }
}
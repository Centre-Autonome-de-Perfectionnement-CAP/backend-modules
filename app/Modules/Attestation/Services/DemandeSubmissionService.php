<?php

namespace App\Modules\Attestation\Services;

use App\Exceptions\BusinessException;
use App\Modules\Demandes\Services\{DocumentStorageService, NotificationService, WhatsAppService};
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;

/**
 * CORRECTIF (v2) — Service extrait du DemandeController réel (site vitrine)
 *
 * Important : la v1 que j'avais livrée précédemment était INCORRECTE sur
 * plusieurs points, car reconstruite sans le vrai code source. Cette version
 * corrige fidèlement d'après le contrôleur réel fourni :
 *
 *   1. Le vrai contrôleur N'UTILISE PAS EligibilityService::checkAvailabilityForType()
 *      avant soumission — il ne vérifie QUE : étudiant existe, inscription approuvée
 *      existe, et absence de demande active du même type. Aucune vérification
 *      d'éligibilité métier (passage/définitive/inscription) n'est faite côté
 *      soumission publique — uniquement côté lecture (getStatus). J'avais ajouté
 *      à tort cette vérification dans ma v1, ce qui aurait bloqué des soumissions
 *      valides. CORRIGÉ : supprimée.
 *
 *   2. La normalisation du numéro WhatsApp passe par
 *      WhatsAppService::normalizePhone(), pas un simple champ brut. CORRIGÉ.
 *
 *   3. Le stockage utilise DocumentStorageService::ensureStructure() puis
 *      storeDemandeFiles() (méthode par lot), pas une méthode storeRequestFile()
 *      qui n'existe pas dans le vrai service. CORRIGÉ.
 *
 *   4. La référence est 'ATT-' . Str::upper(Str::random(4)) — 4 caractères,
 *      pas une référence datée sur 6 caractères comme dans ma v1. CORRIGÉ.
 *
 *   5. Le paiement Trésor Public en ligne génère un PDF de quittance via
 *       Barryvdh\DomPDF — absent de ma v1. AJOUTÉ.
 *
 *   6. La notification utilise NotificationService::sendSoumission(),
 *      pas une méthode sendSubmissionConfirmation() qui n'existe pas. CORRIGÉE.
 *
 *   7. L'insertion se fait via DB::table()->insertGetId(), pas via le modèle
 *      Eloquent DocumentRequest::create() (qui omettrait des colonnes non
 *      fillable comme 'type' ou 'reference'). CORRIGÉ.
 */
class DemandeSubmissionService
{
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

    private const INACTIVE_STATUSES = ['rejected', 'picked_up'];

    public function __construct(
        protected NotificationService    $notificationService,
        protected WhatsAppService        $whatsAppService,
        protected DocumentStorageService $storageService,
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // ATTESTATIONS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    public function submitAttestationRequest(Request $request): array
    {
        $whatsappNormalized = $this->whatsAppService->normalizePhone($request->whatsapp);
        if (!$whatsappNormalized) {
            throw new BusinessException(
                'Numéro WhatsApp invalide. Formats acceptés : 97123456, 0197123456, +22997123456.',
                'INVALID_WHATSAPP',
                422
            );
        }

        $student = Student::where('student_id_number', strtoupper(trim($request->matricule)))->first();
        if (!$student) {
            throw new BusinessException('Étudiant introuvable.', 'STUDENT_NOT_FOUND', 404);
        }

        $link = StudentPendingStudent::where('student_id', $student->id)
            ->whereHas('pendingStudent', fn ($q) => $q->where('status', 'approved'))
            ->with(['pendingStudent.academicYear', 'pendingStudent.personalInformation'])
            ->latest('id')
            ->first();

        if (!$link) {
            throw new BusinessException('Inscription approuvée introuvable.', 'APPROVED_LINK_NOT_FOUND', 404);
        }

        $this->assertNoActivePendingRequest($link->id, $request->type);

        $reference = 'ATT-' . Str::upper(Str::random(4));

        $this->storageService->ensureStructure($request->type, $reference);

        $fileKeys     = self::ATTESTATION_FILES[$request->type] ?? [];
        $filesToStore = [];
        foreach ($fileKeys as $key) {
            if ($request->hasFile($key)) {
                $filesToStore[$key] = $request->file($key);
            }
        }
        $filesData = $this->storageService->storeDemandeFiles($request->type, $reference, $filesToStore);

        $paymentMethod    = $request->payment_method ?? 'manual';
        $paymentReference = $request->payment_reference;
        $montant          = self::MONTANTS_ATTESTATION[$request->type] ?? 2000;

        if ($paymentMethod === 'tresor_online' && $paymentReference) {
            $this->generateQuittancePdf($request, $link, $student, $reference, $paymentReference, $montant, $filesData);
        }

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

        // Notification : ne doit jamais faire échouer la réponse HTTP.
        try {
            $this->notificationService->sendSoumission($demande);
        } catch (\Throwable $e) {
            Log::error('[DemandeSubmissionService] Erreur notification soumission (attestation)', [
                'error'     => $e->getMessage(),
                'reference' => $reference,
            ]);
        }

        return ['reference' => $reference];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // BULLETINS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    public function submitBulletinRequest(Request $request): array
    {
        $whatsappNormalized = $this->whatsAppService->normalizePhone($request->whatsapp);
        if (!$whatsappNormalized) {
            throw new BusinessException(
                'Numéro WhatsApp invalide. Formats acceptés : 97123456, 0197123456, +22997123456.',
                'INVALID_WHATSAPP',
                422
            );
        }

        $link = StudentPendingStudent::with(['pendingStudent', 'pendingStudent.academicYear'])
            ->find($request->link_id);

        if (!$link || $link->pendingStudent?->status !== 'approved') {
            throw new BusinessException('Inscription introuvable ou non approuvée.', 'APPROVED_LINK_NOT_FOUND', 404);
        }

        $this->assertNoActivePendingRequest($link->id, 'bulletin_annuel');

        $reference = 'BUL-' . Str::upper(Str::random(4));

        $this->storageService->ensureStructure('bulletin_annuel', $reference);

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

        try {
            $this->notificationService->sendSoumission($demande);
        } catch (\Throwable $e) {
            Log::error('[DemandeSubmissionService] Erreur notification soumission (bulletin)', [
                'error'     => $e->getMessage(),
                'reference' => $reference,
            ]);
        }

        return ['reference' => $reference];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SUIVI
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    public function getSuivi(string $reference): array
    {
        $demande = DB::table('document_requests')
            ->where('reference', strtoupper(trim($reference)))
            ->first();

        if (!$demande) {
            throw new BusinessException('Demande introuvable.', 'DEMANDE_NOT_FOUND', 404);
        }

        $flagReason = null;
        if (!empty($demande->has_flag)) {
            $history = DB::table('document_request_histories')
                ->where('document_request_id', $demande->id)
                ->where('action', 'like', '%_flagged')
                ->latest('id')
                ->first();
            $flagReason = $history ? $history->comment : 'Dossier validé sous réserve.';
        }

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

        return [
            'reference'       => $demande->reference,
            'type'            => $demande->type,
            'status'          => $demande->status,
            'has_flag'        => !empty($demande->has_flag),
            'flag_reason'     => $flagReason,
            'submitted_at'    => $demande->submitted_at,
            'rejected_reason' => $demande->status === 'rejected' ? $demande->rejected_reason : null,
            'student'         => $studentData ? [
                'last_name'   => $studentData->last_name,
                'first_names' => $studentData->first_names,
                'matricule'   => $studentData->matricule,
            ] : null,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    private function assertNoActivePendingRequest(int $linkId, string $type): void
    {
        $existing = DB::table('document_requests')
            ->where('student_pending_student_id', $linkId)
            ->where('type', $type)
            ->whereNotIn('status', self::INACTIVE_STATUSES)
            ->first();

        if ($existing) {
            throw new BusinessException(
                $type === 'bulletin_annuel'
                    ? 'Une demande de bulletin est déjà en cours pour cette année académique.'
                    : "Une demande active existe déjà pour ce type d'attestation.",
                'DUPLICATE_REQUEST',
                409
            );
        }
    }

    private function generateQuittancePdf(
        Request $request, StudentPendingStudent $link, Student $student,
        string $reference, string $paymentReference, int $montant, array &$filesData
    ): void {
        try {
            $pi  = $link->pendingStudent->personalInformation;
            $nom = trim(($pi->last_name ?? '') . ' ' . ($pi->first_names ?? ''));
            $pdf = Pdf::loadView('core::pdf.quittance', [
                'reference'        => $reference,
                'matricule'        => $student->student_id_number,
                'nomEtudiant'      => $nom,
                'typeDocument'     => self::TYPE_LABELS[$request->type] ?? $request->type,
                'montant'          => number_format($montant, 0, ',', ' ') . ' FCFA',
                'paymentReference' => $paymentReference,
                'date'             => now()->format('d/m/Y à H:i'),
            ]);
            $quittancePath = $this->storageService->storePdfContent(
                $request->type, $reference, 'quittance-online.pdf', $pdf->output()
            );
            $filesData['quittance_online'] = $quittancePath;
        } catch (\Exception $e) {
            Log::error('[DemandeSubmissionService] Erreur génération quittance', [
                'error'     => $e->getMessage(),
                'reference' => $reference,
            ]);
        }
    }
}

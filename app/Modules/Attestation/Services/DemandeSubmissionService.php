<?php

namespace App\Modules\Attestation\Services;

use App\Exceptions\BusinessException;
use App\Modules\Demandes\Services\{DocumentStorageService, NotificationService, WhatsAppService};
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use App\Modules\LegacyStudent\Models\LegacyStudent;
use App\Modules\LegacyStudent\Services\LegacyDeclarationService;
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

    /**
     * Pièces OBLIGATOIRES par type — miroir exact des contrôles du site vitrine
     * (AttestationsForm::validateDocuments). La quittance n'est pas exigée en
     * paiement Trésor en ligne (une quittance PDF est générée côté serveur).
     */
    private const REQUIRED_ATTESTATION_FILES = [
        'attestation_definitive'  => ['demande_manuscrite', 'acte_naissance', 'attestation_succes_file'],
        'attestation_inscription' => ['demande_manuscrite', 'recu_paiement'],
        'attestation_passage'     => ['demande_manuscrite', 'acte_naissance', 'recu_paiement', 'bulletin'],
    ];

    private const FILE_LABELS = [
        'demande_manuscrite'      => 'Demande manuscrite',
        'acte_naissance'          => 'Acte de naissance',
        'attestation_succes_file' => 'Attestation de succès',
        'quittance'               => 'Quittance',
        'recu_paiement'           => 'Reçus de paiement',
        'bulletin'                => 'Bulletin de notes',
    ];

    public function __construct(
        protected NotificationService    $notificationService,
        protected WhatsAppService        $whatsAppService,
        protected DocumentStorageService $storageService,
        protected EligibilityService     $eligibilityService,
        protected LegacyDeclarationService $legacyDeclarations,
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
            // Ancien étudiant déclaré (avant 2023) : circuit officiel, vérification
            // manuelle par la secrétaire (décisions D1/D2).
            $legacy = $this->legacyDeclarations->findActiveByMatricule((string) $request->matricule);
            if ($legacy) {
                return $this->submitLegacyAttestationRequest($request, $legacy, $whatsappNormalized);
            }
            throw new BusinessException('Étudiant introuvable.', 'STUDENT_NOT_FOUND', 404);
        }

        // Éligibilité sur l'ensemble des inscriptions / parcours : la demande est
        // rattachée à l'inscription (et à l'année) qui ouvre ce droit.
        $targets = $this->eligibilityService->attestationTargets($student->id);

        if (!$targets['attestation_inscription']) {
            throw new BusinessException('Inscription approuvée introuvable.', 'APPROVED_LINK_NOT_FOUND', 404);
        }

        // Une seule demande active par type, toutes inscriptions confondues.
        $this->assertNoActivePendingRequest(
            'student_pending_student_id',
            StudentPendingStudent::where('student_id', $student->id)->pluck('id')->all(),
            $request->type,
        );

        // AJOUT (audit 13/10/2026) — contrôles serveur alignés sur le site vitrine.
        // Le client est falsifiable : sans ces contrôles, une requête forgée
        // pouvait soumettre un type non éligible ou un dossier sans pièces.
        $target = $targets[$request->type] ?? null;
        if (!$target) {
            throw new BusinessException(
                "Vous n'êtes pas éligible à ce type d'attestation pour le moment.",
                'NOT_ELIGIBLE',
                422
            );
        }
        $link = $target['link'];
        $this->assertRequiredFiles($request, $request->type);

        $reference = $this->generateReference('ATT');

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
            $pi = $link->pendingStudent->personalInformation;
            $this->generateQuittancePdf(
                $request,
                trim(($pi->last_name ?? '') . ' ' . ($pi->first_names ?? '')),
                $student->student_id_number,
                $reference, $paymentReference, $montant, $filesData
            );
        }

        $id = DB::table('document_requests')->insertGetId([
            'student_pending_student_id' => $link->id,
            'academic_year_id'           => $target['academic_year_id'],
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

        $demande = DB::table('document_requests as dr')->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id')->where('dr.id', $id)->select(['dr.*', DB::raw('COALESCE(ay.academic_year, dr.bulletin_year) as academic_year')])->first();

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

        // Ancien étudiant : pas de lien d'inscription, année choisie librement (D3).
        if (!$request->filled('link_id') && $request->filled('legacy_matricule')) {
            $legacy = $this->legacyDeclarations->findActiveByMatricule((string) $request->legacy_matricule);
            if (!$legacy) {
                throw new BusinessException(
                    "Aucune déclaration d'ancien étudiant active pour ce matricule.",
                    'LEGACY_NOT_FOUND',
                    404
                );
            }
            return $this->submitLegacyBulletinRequest($request, $legacy, $whatsappNormalized);
        }

        $link = StudentPendingStudent::with(['pendingStudent', 'pendingStudent.academicYear'])
            ->find($request->link_id);

        if (!$link || $link->pendingStudent?->status !== 'approved') {
            throw new BusinessException('Inscription introuvable ou non approuvée.', 'APPROVED_LINK_NOT_FOUND', 404);
        }

        $this->assertNoActivePendingRequest('student_pending_student_id', $link->id, 'bulletin_annuel');

        $reference = $this->generateReference('BUL');

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

        $demande = DB::table('document_requests as dr')->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id')->where('dr.id', $id)->select(['dr.*', DB::raw('COALESCE(ay.academic_year, dr.bulletin_year) as academic_year')])->first();

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
    // ANCIENS ÉTUDIANTS (déclarés, avant 2023) — décisions D1/D2/D3
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Tous les types d'attestation sont acceptés (D2) : l'éligibilité est
     * vérifiée manuellement par la secrétaire, qui doit aussi valider la
     * déclaration avant de transmettre au comptable (D1).
     *
     * @throws BusinessException
     */
    private function submitLegacyAttestationRequest(Request $request, LegacyStudent $legacy, string $whatsappNormalized): array
    {
        $this->assertNoActivePendingRequest('legacy_student_id', $legacy->id, $request->type);
        $this->assertRequiredFiles($request, $request->type);

        $reference = $this->generateReference('ATT');
        $this->storageService->ensureStructure($request->type, $reference);

        $filesToStore = [];
        foreach (self::ATTESTATION_FILES[$request->type] ?? [] as $key) {
            if ($request->hasFile($key)) {
                $filesToStore[$key] = $request->file($key);
            }
        }
        $filesData = $this->storageService->storeDemandeFiles($request->type, $reference, $filesToStore);

        $paymentMethod    = $request->payment_method ?? 'manual';
        $paymentReference = $request->payment_reference;

        if ($paymentMethod === 'tresor_online' && $paymentReference) {
            $this->generateQuittancePdf(
                $request,
                trim("{$legacy->last_name} {$legacy->first_name}"),
                $legacy->matricule,
                $reference, $paymentReference, self::MONTANTS_ATTESTATION[$request->type] ?? 2000, $filesData
            );
        }

        return $this->insertLegacyRequest($legacy, [
            'type'              => $request->type,
            'reference'         => $reference,
            'email'             => $request->email,
            'demandeur_whatsapp'=> $whatsappNormalized,
            'payment_method'    => $paymentMethod,
            'payment_reference' => $paymentReference,
            'files'             => json_encode($filesData),
        ]);
    }

    /**
     * Bulletin d'un ancien étudiant : l'année est choisie par l'étudiant (D3)
     * et vérifiée par la secrétaire.
     *
     * @throws BusinessException
     */
    private function submitLegacyBulletinRequest(Request $request, LegacyStudent $legacy, string $whatsappNormalized): array
    {
        $year = self::normalizeBulletinYear((string) $request->bulletin_year);
        if (!$year) {
            throw new BusinessException(
                "Année du bulletin invalide (format attendu : AAAA-AAAA, ex. 2019-2020).",
                'INVALID_BULLETIN_YEAR',
                422
            );
        }

        $this->assertNoActivePendingRequest('legacy_student_id', $legacy->id, 'bulletin_annuel', $year);

        $reference = $this->generateReference('BUL');
        $this->storageService->ensureStructure('bulletin_annuel', $reference);

        $filesToStore = [];
        foreach (['demande_manuscrite', 'acte_naissance', 'quittance'] as $key) {
            if ($request->hasFile($key)) {
                $filesToStore[$key] = $request->file($key);
            }
        }
        $filesData = $this->storageService->storeDemandeFiles('bulletin_annuel', $reference, $filesToStore);

        return $this->insertLegacyRequest($legacy, [
            'type'              => 'bulletin_annuel',
            'reference'         => $reference,
            'bulletin_year'     => $year,
            'email'             => $request->email,
            'demandeur_whatsapp'=> $whatsappNormalized,
            'payment_method'    => $request->payment_method ?? 'manual',
            'payment_reference' => $request->payment_reference ?? null,
            'files'             => json_encode($filesData),
        ]);
    }

    /**
     * Année de bulletin « AAAA-AAAA » (années consécutives, plausibles).
     * Renvoie la valeur normalisée ou null.
     */
    public static function normalizeBulletinYear(string $value): ?string
    {
        if (!preg_match('/^\s*(\d{4})\s*[-\/]\s*(\d{4})\s*$/', $value, $m)) {
            return null;
        }
        [$start, $end] = [(int) $m[1], (int) $m[2]];
        if ($end !== $start + 1 || $start < 1970 || $start > (int) now()->year) {
            return null;
        }
        return "{$start}-{$end}";
    }

    private function insertLegacyRequest(LegacyStudent $legacy, array $columns): array
    {
        $id = DB::table('document_requests')->insertGetId(array_merge([
            'student_pending_student_id' => null,
            'legacy_student_id'          => $legacy->id,
            'status'                     => 'submitted',
            'department_name'            => $legacy->department?->name,
            'submitted_at'               => now(),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ], $columns));

        $demande = DB::table('document_requests as dr')
            ->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id')
            ->where('dr.id', $id)
            ->select(['dr.*', DB::raw('COALESCE(ay.academic_year, dr.bulletin_year) as academic_year')])
            ->first();

        try {
            $this->notificationService->sendSoumission($demande);
        } catch (\Throwable $e) {
            Log::error('[DemandeSubmissionService] Erreur notification soumission (ancien étudiant)', [
                'error'     => $e->getMessage(),
                'reference' => $columns['reference'] ?? null,
            ]);
        }

        return ['reference' => $columns['reference']];
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
                ->where('action_type', 'validation_flagged')
                ->latest('id')
                ->first();
            $flagReason = $history ? $history->comment : 'Dossier validé sous réserve.';
        }

        if (!empty($demande->legacy_student_id)) {
            $studentData = DB::table('legacy_students')
                ->where('id', $demande->legacy_student_id)
                ->select('last_name', 'first_name as first_names', 'matricule', 'status as declaration_status')
                ->first();
        } else {
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
        }

        return [
            'reference'       => $demande->reference,
            'type'            => $demande->type,
            'status'          => $demande->status,
            'has_flag'        => !empty($demande->has_flag),
            'flag_reason'     => $flagReason,
            'submitted_at'    => $demande->submitted_at,
            'updated_at'      => $demande->updated_at,
            'academic_year'   => (!empty($demande->academic_year_id)
                ? DB::table('academic_years')->where('id', $demande->academic_year_id)->value('academic_year')
                : null) ?? ($demande->bulletin_year ?? null),
            'rejected_reason' => $demande->status === 'rejected' ? $demande->rejected_reason : null,
            'bulletin_year'   => $demande->bulletin_year ?? null,
            'is_legacy'       => !empty($demande->legacy_student_id),
            'student'         => $studentData ? [
                'last_name'   => $studentData->last_name,
                'first_names' => $studentData->first_names,
                'matricule'   => $studentData->matricule,
                // Ancien étudiant : état de la déclaration (pending|validated)
                'declaration_status' => $studentData->declaration_status ?? null,
            ] : null,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * @throws BusinessException
     */
    private function assertNoActivePendingRequest(string $ownerColumn, int|array $ownerId, string $type, ?string $bulletinYear = null): void
    {
        $existing = DB::table('document_requests')
            ->whereIn($ownerColumn, (array) $ownerId)
            ->where('type', $type)
            ->when($bulletinYear !== null, fn ($q) => $q->where('bulletin_year', $bulletinYear))
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

    /**
     * Génère une référence unique (ex : ATT-7KQ2XM).
     *
     * CORRECTIF : 4 caractères aléatoires sans contrôle d'unicité → collision
     * possible (contrainte UNIQUE → erreur 500 à la soumission) et référence
     * trop facile à deviner, alors qu'elle suffit à consulter le suivi public
     * (nom + matricule) et à déposer un complément. Désormais 6 caractères
     * (≈ 2 milliards de combinaisons) sans caractères ambigus, et unicité vérifiée.
     */
    private function generateReference(string $prefix): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sans 0/O/1/I

        for ($i = 0; $i < 10; $i++) {
            $code = '';
            for ($j = 0; $j < 6; $j++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = "{$prefix}-{$code}";

            if (!DB::table('document_requests')->where('reference', $reference)->exists()) {
                return $reference;
            }
        }

        throw new BusinessException(
            'Impossible de générer une référence unique. Veuillez réessayer.',
            'REFERENCE_GENERATION_FAILED',
            503
        );
    }

    /**
     * @throws BusinessException
     */

    /**
     * @throws BusinessException
     */
    private function assertRequiredFiles(Request $request, string $type): void
    {
        $required = self::REQUIRED_ATTESTATION_FILES[$type] ?? [];

        $isTresorOnline = ($request->payment_method ?? 'manual') === 'tresor_online'
            && !empty($request->payment_reference);
        if (!$isTresorOnline) {
            $required[] = 'quittance';
        }

        $missing = array_values(array_filter($required, fn ($key) => !$request->hasFile($key)));

        if ($missing) {
            $labels = array_map(fn ($k) => self::FILE_LABELS[$k] ?? $k, $missing);
            throw new BusinessException(
                'Pièce(s) obligatoire(s) manquante(s) : ' . implode(', ', $labels) . '.',
                'MISSING_FILES',
                422
            );
        }
    }

    private function generateQuittancePdf(
        Request $request, string $nom, string $matricule,
        string $reference, string $paymentReference, int $montant, array &$filesData
    ): void {
        try {
            $pdf = Pdf::loadView('core::pdf.quittance', [
                'reference'        => $reference,
                'matricule'        => $matricule,
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

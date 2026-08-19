<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Services\DocumentStorageService;
use App\Modules\Demandes\Services\NotificationService;
use App\Modules\Demandes\Services\WhatsAppService;
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

/**
 * Complément de dossier — site vitrine CAP-EPAC
 *
 * GET  /api/attestations/demandes/complement/find
 * POST /api/attestations/demandes/complement
 *
 * Les fichiers complémentaires sont stockés dans complement/
 * sans jamais écraser demande/. La priorité de lecture est gérée
 * par DocumentStorageService::resolveDocuments().
 *
 * WhatsApp est obligatoire à la soumission du complément.
 * Le numéro est normalisé côté backend avant stockage.
 *
 * Notifications déclenchées automatiquement :
 *  → Étudiant   : email + WhatsApp (accusé de réception)
 *  → Secrétaire : email + WhatsApp (nouveau complément à traiter)
 */
class ComplementDossierController extends Controller
{
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 Mo

    private const FILE_LABELS = [
        'demande_manuscrite'      => 'Demande manuscrite',
        'acte_naissance'          => 'Acte de naissance',
        'attestation_succes_file' => 'Attestation de succès',
        'quittance'               => 'Quittance',
        'recu_paiement'           => 'Reçus de paiement',
        'bulletin'                => 'Bulletin de notes',
        'lettre'                  => 'Lettre de demande',
        'document_1'              => 'Document complémentaire 1',
        'document_2'              => 'Document complémentaire 2',
    ];

    private const TYPE_LABELS = [
        'attestation_passage'     => 'Attestation de Passage',
        'attestation_definitive'  => 'Attestation Définitive',
        'attestation_inscription' => "Attestation d'Inscription",
        'bulletin_annuel'         => 'Bulletin de Notes',
    ];

    private const TYPE_TO_FOLDER = [
        'attestation_definitive'  => 'definitive',
        'attestation_inscription' => 'inscription',
        'attestation_passage'     => 'passage',
    ];

    public function __construct(
        protected NotificationService    $notificationService,
        protected WhatsAppService        $whatsAppService,
        protected DocumentStorageService $storageService,
    ) {}

    // ── GET /api/attestations/demandes/complement/find ────────────────────────

    public function find(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'nullable|string|max:50',
            'matricule' => 'nullable|string|max:50',
        ]);

        if (!$request->filled('reference') && !$request->filled('matricule')) {
            return response()->json([
                'message' => 'Veuillez fournir un numéro de référence ou un matricule.',
            ], 422);
        }

        // Recherche par référence → résultat unique
        if ($request->filled('reference')) {
            $demande = DB::table('document_requests')
                ->where('reference', strtoupper(trim($request->reference)))
                ->first();

            if (!$demande) {
                return response()->json(['message' => 'Aucune demande trouvée pour cette référence.'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $this->formatDemande($demande),
            ]);
        }

        // Recherche par matricule → tableau
        $student = Student::where(
            'student_id_number', strtoupper(trim($request->matricule))
        )->first();

        if (!$student) {
            return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);
        }

        $demandes = DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->where('sps.student_id', $student->id)
            ->select('dr.*')
            ->orderByDesc('dr.id')
            ->get();

        if ($demandes->isEmpty()) {
            return response()->json(['message' => 'Aucune demande trouvée pour ce matricule.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $demandes->map(fn($d) => $this->formatDemande($d))->values(),
        ]);
    }

    // ── POST /api/attestations/demandes/complement ────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'reference'      => 'required|string|max:50',
            'email'          => 'required|email|max:255',
            'whatsapp'       => 'required|string|max:30',
            'pieces'         => 'required|array|min:1',
            'pieces.*'       => 'file|max:5120|mimes:pdf,jpg,jpeg,png',
            'piece_labels'   => 'nullable|array',
            'piece_labels.*' => 'nullable|string|max:200',
        ]);

        $whatsappNormalized = $this->whatsAppService->normalizePhone($request->whatsapp);
        if (!$whatsappNormalized) {
            return response()->json([
                'message' => 'Numéro WhatsApp invalide. Formats acceptés : 97123456, 0197123456, +22997123456.',
                'errors'  => ['whatsapp' => ['Numéro WhatsApp invalide.']],
            ], 422);
        }

        $reference = strtoupper(trim($request->reference));

        $demande = DB::table('document_requests')
            ->where('reference', $reference)
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Référence introuvable.'], 404);
        }

        // Infos étudiant pour les notifications
        $link = StudentPendingStudent::with([
            'student',
            'pendingStudent.personalInformation',
        ])->find($demande->student_pending_student_id);

        $personal   = $link?->pendingStudent?->personalInformation;
        $nomComplet = strtoupper(trim(
            ($personal?->last_name ?? '') . ' ' . ($personal?->first_names ?? '')
        ));
        $matricule  = $link?->student?->student_id_number ?? '—';

        // Récupérer l'existant en complement/
        $existingComplement = is_string($demande->complement_files)
            ? (json_decode($demande->complement_files, true) ?: [])
            : ((array) ($demande->complement_files ?? []));

        // Valider et collecter les fichiers
        $uploadedFiles = $request->file('pieces') ?? [];
        if (empty($uploadedFiles)) {
            return response()->json(['message' => 'Aucun fichier reçu.'], 422);
        }

        $filesToStore = [];
        foreach ($uploadedFiles as $key => $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                return response()->json(['message' => "Le fichier « {$key} » dépasse 5 Mo."], 422);
            }
            if (!in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
                return response()->json(['message' => "Format non accepté pour « {$key} » (PDF, JPG, PNG uniquement)."], 422);
            }
            $filesToStore[$key] = $file;
        }

        if (empty($filesToStore)) {
            return response()->json(['message' => 'Aucun fichier valide reçu.'], 422);
        }

        // Stocker dans complement/ — DocumentStorageService gère la structure
        // et nomme les fichiers d'après leur clé métier
        // S'assurer que la structure existe (rétrocompatibilité demandes anciennes)
        $this->storageService->ensureStructure($demande->type, $reference);

        $newEntries = $this->storageService->storeComplementFiles(
            $demande->type,
            $reference,
            $filesToStore
        );

        if (empty($newEntries)) {
            return response()->json(['message' => 'Aucun fichier valide enregistré.'], 422);
        }

        // Fusion avec l'existant (les nouvelles clés écrasent les anciennes dans complement/)
        $mergedComplement = array_merge($existingComplement, $newEntries);

        // Libellés pour la notification
        $pieceLabels = $request->input('piece_labels', []);
        $savedLabels = [];
        foreach (array_keys($newEntries) as $key) {
            $savedLabels[$key] = $pieceLabels[$key] ?? self::FILE_LABELS[$key] ?? $key;
        }

        $updateData = [
            'complement_files' => json_encode($mergedComplement),
            'complement_at'    => now(),
            'updated_at'       => now(),
        ];

        if (empty($demande->demandeur_whatsapp)) {
            $updateData['demandeur_whatsapp'] = $whatsappNormalized;
        }

        DB::table('document_requests')
            ->where('reference', $reference)
            ->update($updateData);

        $dateComplement = now()
            ->setTimezone('Africa/Porto-Novo')
            ->translatedFormat('d F Y à H\hi');

        $piecesList = array_values($savedLabels);

        // ── Notifications (email + WhatsApp) ──────────────────────────────────
        // sendComplementSecretaire() gère en un appel :
        //   - Email + WhatsApp → étudiant
        //   - Email + WhatsApp → toutes les secrétaires
        $this->notificationService->sendComplementSecretaire(
            etudiantEmail:    $request->email,
            vars:             compact('nomComplet', 'matricule', 'reference', 'dateComplement', 'piecesList'),
            whatsappEtudiant: $whatsappNormalized,
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'message'       => 'Complément de dossier enregistré. Un email de confirmation vous a été envoyé.',
                'reference'     => $reference,
                'complement_at' => $dateComplement,
                'pieces_saved'  => count($newEntries),
            ],
        ], 201);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Formate une demande pour la réponse publique.
     * N'expose PAS le statut ni les dates internes de traitement.
     */
    private function formatDemande(object $demande): array
    {
        $link = StudentPendingStudent::with([
            'student',
            'pendingStudent.personalInformation',
            'pendingStudent.department',
            'pendingStudent.academicYear',
        ])->find($demande->student_pending_student_id);

        $personal  = $link?->pendingStudent?->personalInformation;
        $matricule = $link?->student?->student_id_number ?? '—';

        return [
            'reference'    => $demande->reference,
            'type'         => $demande->type,
            'type_label'   => self::TYPE_LABELS[$demande->type] ?? $demande->type,
            'submitted_at' => $demande->submitted_at
                ? Carbon::parse($demande->submitted_at)->format('d/m/Y à H:i')
                : '—',
            'student' => [
                'last_name'     => $personal?->last_name   ?? '—',
                'first_names'   => $personal?->first_names ?? '—',
                'matricule'     => $matricule,
                'level'         => $link?->pendingStudent?->level ?? '—',
                'department'    => $link?->pendingStudent?->department?->name ?? '—',
                'academic_year' => $link?->pendingStudent?->academicYear?->academic_year ?? '—',
            ],
        ];
    }
}

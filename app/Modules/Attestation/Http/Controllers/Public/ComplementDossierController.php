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

    /**
     * Statuts terminaux : plus aucun complément accepté (dossier clos).
     */
    private const CLOSED_STATUSES = ['rejected', 'picked_up'];

    private const LEGACY_REF_MESSAGE = "Cette référence (REF-…) correspond à une ancienne demande : elle reste consultable "
        . "dans « Suivi des demandes », mais le complément doit être déposé directement au secrétariat.";

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

        // Ancienne référence REF-xxxxxx (système provisoire de main) : consultation seule
        if ($request->filled('reference') && DemandeController::isLegacyServiceReference($request->reference)) {
            return response()->json([
                'message' => self::LEGACY_REF_MESSAGE,
            ], 409);
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

        if ($student) {
            $demandes = DB::table('document_requests as dr')
                ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
                ->where('sps.student_id', $student->id)
                ->select('dr.*')
                ->orderByDesc('dr.id')
                ->get();
        } else {
            // Ancien étudiant déclaré
            $legacyIds = \App\Modules\LegacyStudent\Models\LegacyStudent::whereRaw(
                'UPPER(TRIM(matricule)) = ?', [strtoupper(trim($request->matricule))]
            )->pluck('id');

            if ($legacyIds->isEmpty()) {
                return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);
            }

            $demandes = DB::table('document_requests as dr')
                ->whereIn('dr.legacy_student_id', $legacyIds)
                ->select('dr.*')
                ->orderByDesc('dr.id')
                ->get();
        }

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

        if (DemandeController::isLegacyServiceReference($reference)) {
            return response()->json(['message' => self::LEGACY_REF_MESSAGE], 409);
        }

        $demande = DB::table('document_requests')
            ->where('reference', $reference)
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Référence introuvable.'], 404);
        }

        // AJOUT (audit 13/10/2026) : un dossier clos (rejeté ou déjà retiré)
        // n'accepte plus de complément — sinon les pièces étaient stockées et la
        // secrétaire notifiée pour un dossier qui ne sera plus jamais traité.
        if (in_array($demande->status, self::CLOSED_STATUSES, true)) {
            return response()->json([
                'message' => $demande->status === 'rejected'
                    ? 'Ce dossier a été rejeté : il n\'accepte plus de complément. Veuillez soumettre une nouvelle demande.'
                    : 'Ce dossier est clôturé (document déjà retiré) : il n\'accepte plus de complément.',
            ], 409);
        }

        // Infos étudiant pour les notifications (inscrit ou ancien étudiant)
        $info       = $this->requesterInfo($demande);
        $nomComplet = strtoupper(trim(
            ($info['last_name'] !== '—' ? $info['last_name'] : '') . ' ' . ($info['first_names'] !== '—' ? $info['first_names'] : '')
        ));
        $matricule  = $info['matricule'];

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
            // SÉCURITÉ : la clé sert à nommer le fichier stocké — seules les
            // clés métier connues sont acceptées (pas de nom arbitraire fourni
            // par le client).
            if (!is_string($key) || !array_key_exists($key, self::FILE_LABELS)) {
                return response()->json(['message' => 'Type de pièce non reconnu.'], 422);
            }
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
            // Libellé serveur en priorité (le libellé client n'est qu'un fallback, nettoyé).
            $savedLabels[$key] = self::FILE_LABELS[$key]
                ?? trim(strip_tags((string) ($pieceLabels[$key] ?? $key)));
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
        // Ne doit jamais faire échouer la réponse : les pièces sont déjà enregistrées.
        try {
            $this->notificationService->sendComplementSecretaire(
                etudiantEmail:    $request->email,
                vars:             compact('nomComplet', 'matricule', 'reference', 'dateComplement', 'piecesList'),
                whatsappEtudiant: $whatsappNormalized,
            );
        } catch (\Throwable $e) {
            Log::error('[ComplementDossier] Erreur mise en file des notifications', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
        }

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
        return [
            'reference'    => $demande->reference,
            'type'         => $demande->type,
            'type_label'   => self::TYPE_LABELS[$demande->type] ?? $demande->type,
            'submitted_at' => $demande->submitted_at
                ? Carbon::parse($demande->submitted_at)->format('d/m/Y à H:i')
                : '—',
            'student' => $this->requesterInfo($demande),
        ];
    }

    /**
     * Identité du demandeur : étudiant inscrit (lien d'inscription) ou
     * ancien étudiant déclaré (legacy_students).
     *
     * @return array{last_name: string, first_names: string, matricule: string, level: string, department: string, academic_year: string}
     */
    private function requesterInfo(object $demande): array
    {
        if (!empty($demande->legacy_student_id)) {
            $legacy = \App\Modules\LegacyStudent\Models\LegacyStudent::with('department')->find($demande->legacy_student_id);

            return [
                'last_name'     => $legacy?->last_name  ?? '—',
                'first_names'   => $legacy?->first_name ?? '—',
                'matricule'     => $legacy?->matricule  ?? '—',
                'level'         => '—',
                'department'    => $legacy?->department?->name ?? '—',
                'academic_year' => $demande->bulletin_year ?? '—',
            ];
        }

        $link = StudentPendingStudent::with([
            'student',
            'pendingStudent.personalInformation',
            'pendingStudent.department',
            'pendingStudent.academicYear',
        ])->find($demande->student_pending_student_id);

        $personal = $link?->pendingStudent?->personalInformation;

        return [
            'last_name'     => $personal?->last_name   ?? '—',
            'first_names'   => $personal?->first_names ?? '—',
            'matricule'     => $link?->student?->student_id_number ?? '—',
            'level'         => $link?->pendingStudent?->level ?? '—',
            'department'    => $link?->pendingStudent?->department?->name ?? '—',
            'academic_year' => $link?->pendingStudent?->academicYear?->academic_year ?? '—',
        ];
    }
}

<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Demandes\Services\NotificationService;
use App\Modules\Demandes\Services\WhatsAppService;
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Storage};
use Illuminate\Support\Str;

/**
 * Complément de dossier — site vitrine CAP-EPAC
 *
 * GET  /api/attestations/demandes/complement/find
<<<<<<< HEAD
 * POST /api/attestations/demandes/complement
 *
 * WhatsApp est obligatoire à la soumission du complément.
 * Le numéro est normalisé côté backend avant stockage.
 *
 * Notifications déclenchées automatiquement :
 *  → Étudiant   : email + WhatsApp (accusé de réception)
 *  → Secrétaire : email + WhatsApp (nouveau complément à traiter)
=======
 *      → Retrouve une document_request par référence OU matricule
 *      → Par matricule : retourne TOUTES les demandes liées (l'étudiant choisit)
 *      → Par référence : retourne la demande unique
 *      → Retourne infos basiques : référence, type, date soumission, étudiant
 *
 * POST /api/attestations/demandes/complement
 *      → Enregistre les fichiers dans le dossier existant de la demande
 *      → Stratégie : ÉCRASEMENT par clé (une nouvelle version remplace l'ancienne)
 *      → Stockage : storage/app/public/attestation-demandes/{type}/{REFERENCE}/complement/{key}.{ext}
 *      →           storage/app/public/bulletins-demandes/{REFERENCE}/complement/{key}.{ext}
 *      → Met à jour complement_at sur la demande
 *      → Envoie un mail de confirmation au demandeur
 *      → Envoie une notification au secrétariat
>>>>>>> 11c3939 (Complément dossier)
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
        'bulletin_notes'          => 'Bulletin de Notes',
    ];

<<<<<<< HEAD
=======
    /**
     * Correspond exactement à TYPE_TO_FOLDER dans DemandeController,
     * pour reconstituer le chemin du dossier existant de la demande.
     */
>>>>>>> 11c3939 (Complément dossier)
    private const TYPE_TO_FOLDER = [
        'attestation_definitive'  => 'definitive',
        'attestation_inscription' => 'inscription',
        'attestation_passage'     => 'passage',
    ];

<<<<<<< HEAD
    public function __construct(
        protected NotificationService $notificationService,
        protected WhatsAppService     $whatsAppService,
    ) {}

=======
>>>>>>> 11c3939 (Complément dossier)
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

<<<<<<< HEAD
        // Recherche par référence → résultat unique
=======
        // ── Recherche par référence → résultat unique ─────────────────────────

>>>>>>> 11c3939 (Complément dossier)
        if ($request->filled('reference')) {
            $demande = DB::table('document_requests')
                ->where('reference', strtoupper(trim($request->reference)))
                ->first();

<<<<<<< HEAD
            if (!$demande) {
=======
            if (! $demande) {
>>>>>>> 11c3939 (Complément dossier)
                return response()->json(['message' => 'Aucune demande trouvée pour cette référence.'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $this->formatDemande($demande),
            ]);
        }

<<<<<<< HEAD
        // Recherche par matricule → tableau
=======
        // ── Recherche par matricule → tableau de toutes les demandes ──────────

>>>>>>> 11c3939 (Complément dossier)
        $student = Student::where(
            'student_id_number', strtoupper(trim($request->matricule))
        )->first();

<<<<<<< HEAD
        if (!$student) {
=======
        if (! $student) {
>>>>>>> 11c3939 (Complément dossier)
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

        // Normaliser le numéro WhatsApp
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

        // Infos étudiant
        $link = StudentPendingStudent::with([
            'student',
            'pendingStudent.personalInformation',
        ])->find($demande->student_pending_student_id);

        $personal   = $link?->pendingStudent?->personalInformation;
        $nomComplet = strtoupper(trim(
            ($personal?->last_name ?? '') . ' ' . ($personal?->first_names ?? '')
        ));
        $matricule  = $link?->student?->student_id_number ?? '—';

<<<<<<< HEAD
        // Chemin du dossier
=======
        // ── Chemin du dossier ─────────────────────────────────────────────────
        //
        // Stratégie : on stocke directement sous {key}.{ext} (pas d'horodatage).
        // Déposer un nouveau fichier pour la même clé écrase l'ancienne version.
        //
>>>>>>> 11c3939 (Complément dossier)
        if (str_starts_with($reference, 'BUL-')) {
            $baseFolder = "bulletins-demandes/{$reference}/complement";
        } else {
            $subFolder  = self::TYPE_TO_FOLDER[$demande->type] ?? 'autre';
            $baseFolder = "attestation-demandes/{$subFolder}/{$reference}/complement";
        }

<<<<<<< HEAD
        // Fusion avec l'existant
=======
        // Fusion avec le JSON existant
>>>>>>> 11c3939 (Complément dossier)
        $existingComplement = [];
        if ($demande->complement_files) {
            $decoded = is_string($demande->complement_files)
                ? json_decode($demande->complement_files, true)
                : (array) $demande->complement_files;
            $existingComplement = $decoded ?? [];
        }

        // Stockage des fichiers
        $uploadedFiles = $request->file('pieces') ?? [];
        if (empty($uploadedFiles)) {
            return response()->json(['message' => 'Aucun fichier reçu.'], 422);
        }

        $pieceLabels = $request->input('piece_labels', []);
        $newEntries  = [];
        $savedLabels = [];

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

            $keyNorm  = Str::slug($key, '_');
            $ext      = $file->getClientOriginalExtension() ?: $file->extension();
<<<<<<< HEAD
            $fileName = "{$keyNorm}.{$ext}";

=======

            // Nom de fichier stable : {key}.{ext} — écrase l'ancienne version
            $fileName = "{$keyNorm}.{$ext}";

            // Supprimer l'ancienne version si elle existe (extension différente possible)
>>>>>>> 11c3939 (Complément dossier)
            if (isset($existingComplement[$key])) {
                Storage::disk('public')->delete($existingComplement[$key]);
            }

            $path = $file->storeAs($baseFolder, $fileName, 'public');

            $newEntries[$key]  = $path;
<<<<<<< HEAD
            $savedLabels[$key] = $pieceLabels[$key] ?? self::FILE_LABELS[$key] ?? $key;
=======
            $savedLabels[$key] = $pieceLabels[$key]
                ?? self::FILE_LABELS[$key]
                ?? $key;
>>>>>>> 11c3939 (Complément dossier)
        }

        if (empty($newEntries)) {
            return response()->json(['message' => 'Aucun fichier valide enregistré.'], 422);
        }

<<<<<<< HEAD
=======
        // Merge + sauvegarde BD
>>>>>>> 11c3939 (Complément dossier)
        $mergedComplement = array_merge($existingComplement, $newEntries);

        // Mettre à jour la BD — stocker aussi le WhatsApp si absent
        $updateData = [
            'complement_files' => json_encode($mergedComplement),
            'complement_at'    => now(),
            'updated_at'       => now(),
        ];

        // Si le demandeur_whatsapp n'était pas encore renseigné, on le sauvegarde
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

<<<<<<< HEAD
        // ── Notifications (email + WhatsApp) ──────────────────────────────────
        // sendComplementSecretaire() gère en un appel :
        //   - Email + WhatsApp → étudiant
        //   - Email + WhatsApp → toutes les secrétaires
        $this->notificationService->sendComplementSecretaire(
            etudiantEmail:    $request->email,
            vars:             compact('nomComplet', 'matricule', 'reference', 'dateComplement', 'piecesList'),
            whatsappEtudiant: $whatsappNormalized,
=======
        // Mail étudiant
        $this->sendMail(
            $request->email,
            "Complément de dossier reçu — Réf : {$reference}",
            'core::emails.complement-confirmation',
            compact('nomComplet', 'reference', 'dateComplement', 'piecesList')
        );

        // Notification secrétariat
        $this->sendMail(
            self::SECRETARIAT_EMAIL,
            "Nouveau complément — Réf : {$reference}",
            'core::emails.complement-notification-secretariat',
            compact('nomComplet', 'reference', 'dateComplement', 'piecesList') + ['email' => $request->email]
>>>>>>> 11c3939 (Complément dossier)
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

<<<<<<< HEAD
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
=======
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

    private function sendMail(string $to, string $subject, string $view, array $vars): void
    {
        try {
            Mail::send($view, $vars, fn($m) => $m->to($to)->subject($subject));
        } catch (\Exception $e) {
            Log::error("ComplementDossier — mail [{$to}] : " . $e->getMessage());
        }
>>>>>>> 11c3939 (Complément dossier)
    }
}

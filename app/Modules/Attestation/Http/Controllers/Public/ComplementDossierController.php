<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Mail, Storage};
use Illuminate\Support\Str;

/**
 * Complément de dossier — site vitrine CAP-EPAC
 *
 * GET  /api/attestations/demandes/complement/find
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
 */
class ComplementDossierController extends Controller
{
    // ── Constantes ────────────────────────────────────────────────────────────

    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 Mo

    private const SECRETARIAT_EMAIL = 'secretariat@cap-epac.bj';

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

    /**
     * Correspond exactement à TYPE_TO_FOLDER dans DemandeController,
     * pour reconstituer le chemin du dossier existant de la demande.
     */
    private const TYPE_TO_FOLDER = [
        'attestation_definitive'  => 'definitive',
        'attestation_inscription' => 'inscription',
        'attestation_passage'     => 'passage',
    ];

    // ── GET /api/attestations/demandes/complement/find ────────────────────────

    public function find(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'nullable|string|max:50',
            'matricule' => 'nullable|string|max:50',
        ]);

        if (! $request->filled('reference') && ! $request->filled('matricule')) {
            return response()->json([
                'message' => 'Veuillez fournir un numéro de référence ou un matricule.',
            ], 422);
        }

        // ── Recherche par référence → résultat unique ─────────────────────────

        if ($request->filled('reference')) {
            $demande = DB::table('document_requests')
                ->where('reference', strtoupper(trim($request->reference)))
                ->first();

            if (! $demande) {
                return response()->json(['message' => 'Aucune demande trouvée pour cette référence.'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $this->formatDemande($demande),
            ]);
        }

        // ── Recherche par matricule → tableau de toutes les demandes ──────────

        $student = Student::where(
            'student_id_number', strtoupper(trim($request->matricule))
        )->first();

        if (! $student) {
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
            'pieces'         => 'required|array|min:1',
            'pieces.*'       => 'file|max:5120|mimes:pdf,jpg,jpeg,png',
            'piece_labels'   => 'nullable|array',
            'piece_labels.*' => 'nullable|string|max:200',
        ]);

        $reference = strtoupper(trim($request->reference));

        $demande = DB::table('document_requests')
            ->where('reference', $reference)
            ->first();

        if (! $demande) {
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

        // ── Chemin du dossier ─────────────────────────────────────────────────
        //
        // Stratégie : on stocke directement sous {key}.{ext} (pas d'horodatage).
        // Déposer un nouveau fichier pour la même clé écrase l'ancienne version.
        //
        if (str_starts_with($reference, 'BUL-')) {
            $baseFolder = "bulletins-demandes/{$reference}/complement";
        } else {
            $subFolder  = self::TYPE_TO_FOLDER[$demande->type] ?? 'autre';
            $baseFolder = "attestation-demandes/{$subFolder}/{$reference}/complement";
        }

        // Fusion avec le JSON existant
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
            if (! $file || ! $file->isValid()) {
                continue;
            }

            if ($file->getSize() > self::MAX_FILE_SIZE) {
                return response()->json([
                    'message' => "Le fichier « {$key} » dépasse 5 Mo.",
                ], 422);
            }

            if (! in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
                return response()->json([
                    'message' => "Format non accepté pour « {$key} » (PDF, JPG, PNG uniquement).",
                ], 422);
            }

            $keyNorm  = Str::slug($key, '_');
            $ext      = $file->getClientOriginalExtension() ?: $file->extension();

            // Nom de fichier stable : {key}.{ext} — écrase l'ancienne version
            $fileName = "{$keyNorm}.{$ext}";

            // Supprimer l'ancienne version si elle existe (extension différente possible)
            if (isset($existingComplement[$key])) {
                Storage::disk('public')->delete($existingComplement[$key]);
            }

            $path = $file->storeAs($baseFolder, $fileName, 'public');

            $newEntries[$key]  = $path;
            $savedLabels[$key] = $pieceLabels[$key]
                ?? self::FILE_LABELS[$key]
                ?? $key;
        }

        if (empty($newEntries)) {
            return response()->json(['message' => 'Aucun fichier valide enregistré.'], 422);
        }

        // Merge + sauvegarde BD
        $mergedComplement = array_merge($existingComplement, $newEntries);

        DB::table('document_requests')
            ->where('reference', $reference)
            ->update([
                'complement_files' => json_encode($mergedComplement),
                'complement_at'    => now(),
                'updated_at'       => now(),
            ]);

        $dateComplement = now()
            ->setTimezone('Africa/Porto-Novo')
            ->translatedFormat('d F Y à H\hi');

        $piecesList = array_values($savedLabels);

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

    private function sendMail(string $to, string $subject, string $view, array $vars): void
    {
        try {
            Mail::send($view, $vars, fn($m) => $m->to($to)->subject($subject));
        } catch (\Exception $e) {
            Log::error("ComplementDossier — mail [{$to}] : " . $e->getMessage());
        }
    }
}

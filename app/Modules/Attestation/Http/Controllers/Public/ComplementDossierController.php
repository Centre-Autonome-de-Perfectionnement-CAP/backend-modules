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
 *      → Vérifie que complement_pieces_requises est renseigné
 *      → Retourne les infos + la liste des pièces demandées
 *
 * POST /api/attestations/demandes/complement
 *      → Enregistre les fichiers dans complement_files (JSON fusionné)
 *      → Stockage : storage/{MATRICULE}/complement/{Ymd_His}_{key}.{ext}
 *      → Met à jour complement_at sur la demande
 *      → Envoie un mail de confirmation au demandeur
 *      → Envoie une notification au secrétariat
 *
 * Namespace : App\Modules\Attestation\Http\Controllers\Public
 * Route file : app/Modules/Attestation/routes/api.php
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
        'recu_paiement'           => 'Reçu de paiement',
        'bulletin'                => 'Bulletin de notes',
    ];

    private const TYPE_LABELS = [
        'attestation_passage'     => 'Attestation de Passage',
        'attestation_definitive'  => 'Attestation Définitive',
        'attestation_inscription' => "Attestation d'Inscription",
        'bulletin_notes'          => 'Bulletin de Notes',
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

        $demande   = null;
        $matricule = null;

        // Recherche par référence
        if ($request->filled('reference')) {
            $demande = DB::table('document_requests')
                ->where('reference', strtoupper(trim($request->reference)))
                ->first();
        }

        // Recherche par matricule (fallback)
        if (! $demande && $request->filled('matricule')) {
            $student = Student::where(
                'student_id_number', strtoupper(trim($request->matricule))
            )->first();

            if (! $student) {
                return response()->json(['message' => 'Aucun étudiant trouvé avec ce matricule.'], 404);
            }

            // Priorité aux demandes ayant des pièces requises
            $demande = DB::table('document_requests as dr')
                ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
                ->where('sps.student_id', $student->id)
                ->whereNotNull('dr.complement_pieces_requises')
                ->select('dr.*')
                ->orderByDesc('dr.id')
                ->first();

            // Fallback : dernière demande quelconque
            if (! $demande) {
                $demande = DB::table('document_requests as dr')
                    ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
                    ->where('sps.student_id', $student->id)
                    ->select('dr.*')
                    ->orderByDesc('dr.id')
                    ->first();
            }
        }

        if (! $demande) {
            return response()->json(['message' => 'Aucune demande trouvée.'], 404);
        }

        // Relations étudiant
        $link = StudentPendingStudent::with([
            'student',
            'pendingStudent.personalInformation',
            'pendingStudent.department',
            'pendingStudent.academicYear',
        ])->find($demande->student_pending_student_id);

        $personal  = $link?->pendingStudent?->personalInformation;
        $matricule = $link?->student?->student_id_number ?? '—';

        // Pièces requises
        $piecesRequises = $demande->complement_pieces_requises ?? null;
        if (is_string($piecesRequises)) {
            $piecesRequises = json_decode($piecesRequises, true) ?? [];
        }
        $piecesRequises = $piecesRequises ?: [];

        if (empty($piecesRequises)) {
            return response()->json([
                'message' => "Aucune pièce complémentaire n'est demandée pour ce dossier pour le moment.",
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'reference'      => $demande->reference,
                'type'           => $demande->type,
                'type_label'     => self::TYPE_LABELS[$demande->type] ?? $demande->type,
                'status'         => $demande->status,
                'status_label'   => $this->resolveStatusLabel($demande->status),
                'submitted_at'   => $demande->submitted_at
                    ? Carbon::parse($demande->submitted_at)->format('d/m/Y à H:i')
                    : '—',
                'complement_at'  => $demande->complement_at
                    ? Carbon::parse($demande->complement_at)->format('d/m/Y à H:i')
                    : null,
                'student' => [
                    'last_name'     => $personal?->last_name    ?? '—',
                    'first_names'   => $personal?->first_names  ?? '—',
                    'matricule'     => $matricule,
                    'level'         => $link?->pendingStudent?->level ?? '—',
                    'department'    => $link?->pendingStudent?->department?->name ?? '—',
                    'academic_year' => $link?->pendingStudent?->academicYear?->academic_year ?? '—',
                ],
                'pieces_requises' => $piecesRequises,
            ],
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

        $student    = $link?->student;
        $personal   = $link?->pendingStudent?->personalInformation;
        $matricule  = strtoupper($student?->student_id_number ?? 'INCONNU');
        $nomComplet = strtoupper(trim(
            ($personal?->last_name ?? '') . ' ' . ($personal?->first_names ?? '')
        ));

        // Fusion avec le JSON existant (pour permettre plusieurs sessions de complément)
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

        $horodatage  = now()->format('Ymd_His');
        $baseFolder  = "{$matricule}/complement";
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
            $fileName = "{$horodatage}_{$keyNorm}.{$ext}";
            $path     = $file->storeAs($baseFolder, $fileName, 'public');

            $newEntries[$key] = $path;
            $savedLabels[$key] = $pieceLabels[$key]
                ?? self::FILE_LABELS[$key]
                ?? $key;
        }

        if (empty($newEntries)) {
            return response()->json(['message' => 'Aucun fichier valide enregistré.'], 422);
        }

        // Mise à jour BD
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
            compact('nomComplet', 'matricule', 'reference', 'dateComplement', 'piecesList')
        );

        // Notification secrétariat
        $this->sendMail(
            self::SECRETARIAT_EMAIL,
            "Nouveau complément — {$matricule} — Réf : {$reference}",
            'core::emails.complement-notification-secretariat',
            compact('nomComplet', 'matricule', 'reference', 'dateComplement', 'piecesList') + ['email' => $request->email]
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

    private function sendMail(string $to, string $subject, string $view, array $vars): void
    {
        try {
            Mail::send($view, $vars, fn($m) => $m->to($to)->subject($subject));
        } catch (\Exception $e) {
            Log::error("ComplementDossier — mail [{$to}] : " . $e->getMessage());
        }
    }

    private function resolveStatusLabel(string $status): string
    {
        $map = [
            'pending'                    => 'En attente',
            'processing'                 => 'En cours de traitement',
            'secretaire_review'          => 'En cours — Secrétariat',
            'secretaire_correction'      => 'Correction demandée',
            'comptable_review'           => 'En cours — Comptabilité',
            'chef_division_review'       => 'En cours — Resp. Division',
            'chef_cap_review'            => 'En cours — Chef CAP',
            'sec_dir_adjointe_review'    => 'En cours — Sec. Dir. Adjointe',
            'directrice_adjointe_review' => 'En cours — Directrice Adjointe',
            'sec_directeur_review'       => 'En cours — Sec. Directeur',
            'directeur_review'           => 'En cours — Directeur',
            'ready'                      => 'Prêt à retirer',
            'delivered'                  => 'Retiré / Archivé',
            'rejected'                   => 'Rejeté',
        ];
        return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\{Student, StudentPendingStudent};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Mail, Storage};

/**
 * Soumission et suivi des demandes de documents (site vitrine)
 *
 * POST /api/attestations/demandes
 * POST /api/attestations/bulletins
 * GET  /api/attestations/demandes/suivi
 */
class DemandeController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // Constantes partagées
    // ──────────────────────────────────────────────────────────────────────────

    private const TYPE_LABELS = [
        'attestation_passage'     => 'Attestation de Passage',
        'attestation_definitive'  => 'Attestation Définitive',
        'attestation_inscription' => "Attestation d'Inscription",
    ];

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

    private const MONTANTS_ATTESTATION = [
        'attestation_passage'     => 2000,
        'attestation_definitive'  => 2000,
        'attestation_inscription' => 2000,
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/attestations/demandes
    // ──────────────────────────────────────────────────────────────────────────

    public function storeDemande(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'         => 'required|string',
            'type'              => 'required|string',
            'email'             => 'required|email',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50',
        ]);

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

        if ($this->hasActiveDemande($link->id, $request->type)) {
            return response()->json([
                'message' => "Vous avez déjà une demande en cours pour ce type d'attestation.",
            ], 422);
        }

        $reference        = 'ATT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $subFolder        = self::TYPE_TO_FOLDER[$request->type] ?? 'autre';
        $paymentMethod    = $request->input('payment_method', 'manual');
        $paymentReference = $request->input('payment_reference');

        $uploadedFiles = $this->storeFiles(
            $request,
            self::ATTESTATION_FILES[$request->type] ?? [],
            "attestation-demandes/{$subFolder}/{$reference}"
        );

        DB::table('document_requests')->insert([
            'reference'                  => $reference,
            'student_pending_student_id' => $link->id,
            'academic_year_id'           => $link->pendingStudent->academic_year_id ?? null,
            'type'                       => $request->type,
            'status'                     => 'pending',
            'email'                      => $request->email,
            'payment_method'             => $paymentMethod,
            'payment_reference'          => $paymentReference,
            'files'                      => !empty($uploadedFiles) ? json_encode($uploadedFiles) : null,
            'submitted_at'               => now(),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        // Générer + attacher la quittance PDF si paiement en ligne
        [$quittancePdfContent, $quittancePdfFilename] = $this->maybeGenerateQuittancePdf(
            $paymentMethod,
            $paymentReference,
            $link,
            $student,
            $reference,
            self::TYPE_LABELS[$request->type] ?? $request->type,
            self::MONTANTS_ATTESTATION[$request->type] ?? 2000
        );

        // Sauvegarder le PDF quittance sur disque
        if ($quittancePdfContent) {
            try {
                $quittancePath = "attestation-demandes/{$subFolder}/{$reference}/{$quittancePdfFilename}";
                Storage::disk('public')->put($quittancePath, $quittancePdfContent);
                $uploadedFiles['quittance_generee'] = $quittancePath;
                DB::table('document_requests')
                    ->where('reference', $reference)
                    ->update(['files' => json_encode($uploadedFiles)]);
            } catch (\Exception $e) {
                Log::warning('Sauvegarde PDF quittance sur disque échouée : ' . $e->getMessage());
            }
        }

        // Envoi email de confirmation
        $this->sendConfirmationEmail(
            'core::emails.attestation-confirmation',
            $request->email,
            "Demande d'attestation reçue — Réf : {$reference}",
            [
                'reference'      => $reference,
                'type'           => $request->type,
                'studentName'    => $student->student_id_number,
                'submittedAt'    => now()->format('d/m/Y à H:i'),
                'paymentMethod'  => $paymentMethod,
                'paymentRef'     => $paymentReference,
            ],
            $quittancePdfContent,
            $quittancePdfFilename
        );

        $quittancePdfUrl = isset($uploadedFiles['quittance_generee'])
            ? Storage::disk('public')->url($uploadedFiles['quittance_generee'])
            : null;

        return response()->json([
            'success' => true,
            'data'    => [
                'message'              => "Demande enregistrée avec succès. Un email de confirmation vous a été envoyé.",
                'reference'            => $reference,
                'payment_method'       => $paymentMethod,
                'payment_reference'    => $paymentReference,
                'quittance_pdf_url'    => $quittancePdfUrl,
                'quittance_pdf_base64' => $quittancePdfContent ? base64_encode($quittancePdfContent) : null,
                'quittance_filename'   => $quittancePdfFilename,
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/attestations/bulletins
    // ──────────────────────────────────────────────────────────────────────────

    public function storeBulletinDemande(Request $request): JsonResponse
    {
        $request->validate([
            'link_id'           => 'required|integer',
            'type'              => 'required|string',
            'email'             => 'required|email',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50',
        ]);

        $link = StudentPendingStudent::with(['pendingStudent.academicYear', 'pendingStudent.personalInformation'])
            ->find($request->link_id);

        if (!$link) {
            return response()->json(['message' => 'Inscription introuvable.'], 404);
        }

        if ($this->hasActiveDemande($link->id, $request->type)) {
            return response()->json([
                'message' => 'Vous avez déjà une demande en cours pour ce bulletin.',
            ], 422);
        }

        $year             = $link->pendingStudent->academicYear;
        $reference        = 'BUL-' . strtoupper(substr(uniqid(), -8));
        $paymentMethod    = $request->input('payment_method', 'manual');
        $paymentReference = $request->input('payment_reference');

        $uploadedFiles = $this->storeFiles(
            $request,
            ['demande_manuscrite', 'acte_naissance', 'quittance'],
            "bulletins-demandes/{$reference}"
        );

        DB::table('document_requests')->insert([
            'reference'                  => $reference,
            'student_pending_student_id' => $link->id,
            'academic_year_id'           => $year?->id,
            'type'                       => $request->type,
            'status'                     => 'pending',
            'email'                      => $request->email,
            'payment_method'             => $paymentMethod,
            'payment_reference'          => $paymentReference,
            'files'                      => !empty($uploadedFiles) ? json_encode($uploadedFiles) : null,
            'submitted_at'               => now(),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        [$quittancePdfContent, $quittancePdfFilename] = $this->maybeGenerateQuittancePdf(
            $paymentMethod,
            $paymentReference,
            $link,
            $link->student,
            $reference,
            'Bulletin de notes — CAP-EPAC',
            500
        );

        $this->sendConfirmationEmail(
            'core::emails.bulletin-confirmation',
            $request->email,
            "Demande de bulletin reçue — Réf : {$reference}",
            [
                'reference'     => $reference,
                'type'          => $request->type,
                'academicYear'  => $year?->academic_year ?? '—',
                'submittedAt'   => now()->format('d/m/Y à H:i'),
                'paymentMethod' => $paymentMethod,
                'paymentRef'    => $paymentReference,
            ],
            $quittancePdfContent,
            $quittancePdfFilename
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'message'           => 'Demande de bulletin enregistrée avec succès. Un email de confirmation vous a été envoyé.',
                'reference'         => $reference,
                'payment_method'    => $paymentMethod,
                'payment_reference' => $paymentReference,
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/attestations/demandes/suivi?reference=ATT-XXXXXXXX
    // ──────────────────────────────────────────────────────────────────────────

    public function suiviDemande(Request $request): JsonResponse
    {
        $request->validate(['reference' => 'required|string']);

        $demande = DB::table('document_requests')
            ->where('reference', strtoupper(trim($request->reference)))
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Aucune demande trouvée avec cette référence.'], 404);
        }

        $link = StudentPendingStudent::with([
            'student',
            'pendingStudent.personalInformation',
            'pendingStudent.department',
            'pendingStudent.academicYear',
        ])->find($demande->student_pending_student_id);

        $personal = $link?->pendingStudent?->personalInformation;

        return response()->json([
            'success' => true,
            'data'    => [
                'reference'       => $demande->reference,
                'type'            => $demande->type,
                'status'          => $demande->status,
                'submitted_at'    => $demande->submitted_at,
                'rejected_reason' => $demande->rejected_reason ?? null,
                'student'         => [
                    'last_name'     => $personal?->last_name ?? '—',
                    'first_names'   => $personal?->first_names ?? '—',
                    'matricule'     => $link?->student?->student_id_number ?? '—',
                    'level'         => $link?->pendingStudent?->level ?? '—',
                    'department'    => $link?->pendingStudent?->department?->name ?? '—',
                    'academic_year' => $link?->pendingStudent?->academicYear?->academic_year ?? '—',
                ],
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers privés
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Vérifie si une demande active (pending/processing/ready) existe déjà.
     */
    private function hasActiveDemande(int $linkId, string $type): bool
    {
        return DB::table('document_requests')
            ->where('student_pending_student_id', $linkId)
            ->where('type', $type)
            ->whereIn('status', ['pending', 'processing', 'ready'])
            ->exists();
    }

    /**
     * Stocke les fichiers uploadés dans le bon sous-dossier.
     * Retourne un tableau ['clé' => 'chemin_stocké'].
     */
    private function storeFiles(Request $request, array $keys, string $folder): array
    {
        $uploaded = [];
        foreach ($keys as $key) {
            if ($request->hasFile($key) && $request->file($key)->isValid()) {
                $file      = $request->file($key);
                $extension = $file->getClientOriginalExtension();
                $fileName  = $key . ($extension ? ".{$extension}" : '');
                $uploaded[$key] = $file->storeAs($folder, $fileName, 'public');
            }
        }
        return $uploaded;
    }

    /**
     * Génère le PDF quittance si le paiement est en ligne.
     * Retourne [pdfContent|null, pdfFilename|null].
     */
    private function maybeGenerateQuittancePdf(
        string $paymentMethod,
        ?string $paymentReference,
        StudentPendingStudent $link,
        $student,
        string $reference,
        string $motif,
        int $montant
    ): array {
        if ($paymentMethod !== 'tresor_online' || !$paymentReference) {
            return [null, null];
        }

        try {
            $personal     = $link->pendingStudent->personalInformation;
            $nomComplet   = strtoupper(trim(($personal->last_name ?? '') . ' ' . ($personal->first_names ?? '')));
            $datePaiement = Carbon::now()
                ->setTimezone('Africa/Porto-Novo')
                ->translatedFormat('d F Y à H\hi');

            $pdf = Pdf::loadView('core::pdfs.quittance-tresor', [
                'quittanceNumber'  => $paymentReference,
                'montant'          => $montant,
                'motif'            => $motif,
                'nomEtudiant'      => $nomComplet,
                'matricule'        => strtoupper($student?->student_id_number ?? ''),
                'referenceDemande' => $reference,
                'datePaiement'     => $datePaiement,
                'simulation'       => true,
            ])->setPaper('A4', 'portrait');

            return [$pdf->output(), 'quittance-' . $paymentReference . '.pdf'];
        } catch (\Exception $e) {
            Log::warning('Génération PDF quittance échouée : ' . $e->getMessage());
            return [null, null];
        }
    }

    /**
     * Envoie un email de confirmation avec pièce jointe optionnelle.
     */
    private function sendConfirmationEmail(
        string $view,
        string $to,
        string $subject,
        array $vars,
        ?string $pdfContent,
        ?string $pdfFilename
    ): void {
        try {
            Mail::send($view, $vars, function ($message) use ($to, $subject, $pdfContent, $pdfFilename) {
                $message->to($to)->subject($subject);
                if ($pdfContent && $pdfFilename) {
                    $message->attachData($pdfContent, $pdfFilename, ['mime' => 'application/pdf']);
                }
            });
        } catch (\Exception $e) {
            Log::error('Échec envoi email : ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Modules\LegacyStudent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LegacyStudent\Models\LegacyStudent;
use App\Modules\LegacyStudent\Models\LegacyStudentServiceRequest;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Contrôleur unifié pour les demandes de services étudiants.
 * Cherche dans les deux sources : students récents (>=2023) ET legacy_students (<2023).
 */
class StudentServiceController extends Controller
{
    use ApiResponse;

    // ─────────────────────────────────────────────────────────────────────────
    // RECHERCHE UNIFIÉE PAR MATRICULE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recherche un étudiant par matricule dans les deux tables.
     * Retourne un tableau normalisé avec source + infos étudiant.
     */
    private function findStudentByMatricule(string $matricule): ?array
    {
        $mat = strtoupper(trim($matricule));

        // 1. Chercher dans les étudiants récents (table students via student_id_number)
        $student = Student::where('student_id_number', $mat)
            ->with(['pendingStudents.personalInformation', 'pendingStudents.department', 'pendingStudents.academicYear'])
            ->first();

        if ($student) {
            $pi = $student->pendingStudents->first()?->personalInformation;
            $ps = $student->pendingStudents->first();
            return [
                'source'        => 'recent',
                'matricule'     => $student->student_id_number,
                'last_name'     => $pi?->last_name ?? '',
                'first_names'   => $pi?->first_names ?? '',
                'level'         => $ps?->level ?? '',
                'department'    => $ps?->department?->name ?? '',
                'academic_year' => $ps?->academicYear?->academic_year ?? '',
                'email'         => $pi?->email ?? '',
            ];
        }

        // 2. Chercher dans les anciens étudiants (table legacy_students)
        $legacy = LegacyStudent::with(['departments', 'department'])
            ->where('matricule', $mat)
            ->first();

        if ($legacy) {
            $filiere = $legacy->department?->name
                ?? $legacy->departments->first()?->name
                ?? '';
            $year = $legacy->enrollment_year
                ? "{$legacy->enrollment_year}-" . ($legacy->enrollment_year + 1)
                : 'Avant 2023';

            return [
                'source'        => 'legacy',
                'matricule'     => $legacy->matricule,
                'last_name'     => $legacy->last_name,
                'first_names'   => $legacy->first_name,
                'level'         => 'Ancien Étudiant (< 2023)',
                'department'    => $filiere,
                'academic_year' => $year,
                'email'         => $legacy->email ?? '',
                'legacy_id'     => $legacy->id,
            ];
        }

        return null;
    }

    /**
     * Récupère les demandes existantes d'un type donné pour un matricule.
     * Cherche dans la table legacy_student_services (tous types confondus).
     */
    private function getExistingRequests(string $matricule, string $serviceType): array
    {
        return LegacyStudentServiceRequest::where('matricule', strtoupper($matricule))
            ->where('service_type', $serviceType)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'type'           => $r->service_type,
                'status'         => $r->status,
                'reference'      => $r->id ? 'REF-' . str_pad($r->id, 6, '0', STR_PAD_LEFT) : null,
                'submittedAt'    => $r->created_at?->toISOString(),
                'rejectedReason' => $r->rejection_reason,
                'service_name'   => $r->service_name,
            ])->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT : GET /api/attestations/status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Statut des attestations pour un étudiant (récent ou ancien).
     * GET /api/attestations/status?matricule=...
     */
    public function attestationsStatus(Request $request): JsonResponse
    {
        $matricule = strtoupper(trim($request->query('matricule', '')));

        if (empty($matricule)) {
            return response()->json(['message' => 'Le matricule est requis.'], 400);
        }

        $found = $this->findStudentByMatricule($matricule);

        if (!$found) {
            return response()->json([
                'message' => 'Aucun étudiant trouvé avec ce matricule. Vérifiez votre saisie ou déclarez-vous si vous êtes un ancien étudiant (avant 2023).',
            ], 404);
        }

        $attestationTypes = ['succes', 'definitive', 'inscription', 'passage'];
        $documents = [];

        foreach ($attestationTypes as $type) {
            $existing = LegacyStudentServiceRequest::where('matricule', $matricule)
                ->where('service_type', $type)
                ->orderByDesc('created_at')
                ->first();

            $documents[] = [
                'type'           => $type,
                'status'         => $existing ? $existing->status : 'disponible',
                'reference'      => $existing ? 'REF-' . str_pad($existing->id, 6, '0', STR_PAD_LEFT) : null,
                'submittedAt'    => $existing?->created_at?->toISOString(),
                'rejectedReason' => $existing?->rejection_reason,
            ];
        }

        return response()->json([
            'student'   => [
                'last_name'     => $found['last_name'],
                'first_names'   => $found['first_names'],
                'matricule'     => $found['matricule'],
                'level'         => $found['level'],
                'department'    => $found['department'],
                'academic_year' => $found['academic_year'],
                'source'        => $found['source'],
            ],
            'documents' => $documents,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT : GET /api/attestations/bulletin-status
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Statut des bulletins pour un étudiant (récent ou ancien).
     * GET /api/attestations/bulletin-status?matricule=...
     */
    public function bulletinStatus(Request $request): JsonResponse
    {
        $matricule = strtoupper(trim($request->query('matricule', '')));

        if (empty($matricule)) {
            return response()->json(['message' => 'Le matricule est requis.'], 400);
        }

        $found = $this->findStudentByMatricule($matricule);

        if (!$found) {
            return response()->json([
                'message' => 'Aucun étudiant trouvé avec ce matricule. Vérifiez votre saisie ou déclarez-vous si vous êtes un ancien étudiant (avant 2023).',
            ], 404);
        }

        $year = $found['academic_year'] ?: 'Année académique';

        // Chercher les demandes de bulletins existantes
        $existingBulletin = LegacyStudentServiceRequest::where('matricule', $matricule)
            ->where('service_type', 'bulletin_annuel')
            ->orderByDesc('created_at')
            ->first();

        $years = [[
            'link_id'       => $existingBulletin?->id ?? 0,
            'academic_year' => $year,
            'year_id'       => 1,
            'is_current'    => true,
            'bulletin'      => [
                'type'           => 'bulletin_annuel',
                'status'         => $existingBulletin ? $existingBulletin->status : 'disponible',
                'reference'      => $existingBulletin ? 'REF-' . str_pad($existingBulletin->id, 6, '0', STR_PAD_LEFT) : null,
                'submittedAt'    => $existingBulletin?->created_at?->toISOString(),
                'rejectedReason' => $existingBulletin?->rejection_reason,
            ],
        ]];

        return response()->json([
            'student' => [
                'last_name'     => $found['last_name'],
                'first_names'   => $found['first_names'],
                'matricule'     => $found['matricule'],
                'level'         => $found['level'],
                'department'    => $found['department'],
                'academic_year' => $found['academic_year'],
                'source'        => $found['source'],
            ],
            'years' => $years,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT : POST /api/attestations/demandes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Soumettre une demande d'attestation (tous types).
     * POST /api/attestations/demandes
     */
    public function submitAttestation(Request $request): JsonResponse
    {
        $request->validate([
            'matricule' => 'required|string',
            'type'      => 'required|string|in:succes,definitive,inscription,passage',
            'email'     => 'required|email',
            'whatsapp'  => 'required|string',
        ]);

        $matricule = strtoupper(trim($request->input('matricule')));
        $found = $this->findStudentByMatricule($matricule);

        if (!$found) {
            return response()->json([
                'message' => 'Matricule introuvable. Veuillez vérifier votre saisie.',
            ], 404);
        }

        return DB::transaction(function () use ($request, $found, $matricule) {
            // Gestion des fichiers uploadés
            $metadata = [
                'payment_method' => $request->input('payment_method', 'manual'),
                'whatsapp'       => $request->input('whatsapp'),
                'source'         => $found['source'],
            ];

            $fileFields = ['demande_manuscrite', 'acte_naissance', 'attestation_succes_file',
                           'recu_paiement', 'bulletin', 'quittance'];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $path = $request->file($field)->store('service-requests/' . $matricule, 'public');
                    $metadata['files'][$field] = $path;
                }
            }

            // Nom lisible du service
            $serviceNames = [
                'succes'      => 'Attestation de Succès',
                'definitive'  => 'Attestation Définitive',
                'inscription' => 'Attestation d\'Inscription',
                'passage'     => 'Attestation de Passage',
            ];

            $serviceRequest = LegacyStudentServiceRequest::create([
                'legacy_student_id' => $found['legacy_id'] ?? null,
                'matricule'         => $matricule,
                'student_name'      => trim($found['last_name'] . ' ' . $found['first_names']),
                'email'             => $request->input('email'),
                'phone'             => $request->input('whatsapp'),
                'service_type'      => $request->input('type'),
                'service_name'      => $serviceNames[$request->input('type')] ?? $request->input('type'),
                'filiere_name'      => $found['department'],
                'enrollment_year'   => null,
                'status'            => 'submitted',
                'metadata'          => $metadata,
            ]);

            $reference = 'REF-' . str_pad($serviceRequest->id, 6, '0', STR_PAD_LEFT);

            Log::info('Demande attestation soumise', [
                'matricule' => $matricule,
                'type'      => $request->input('type'),
                'source'    => $found['source'],
                'reference' => $reference,
            ]);

            return response()->json([
                'success'   => true,
                'message'   => 'Votre demande a été soumise avec succès.',
                'reference' => $reference,
            ], 201);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT : POST /api/attestations/bulletins
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Soumettre une demande de bulletin.
     * POST /api/attestations/bulletins
     */
    public function submitBulletin(Request $request): JsonResponse
    {
        $request->validate([
            'matricule' => 'required|string',
            'email'     => 'required|email',
            'whatsapp'  => 'required|string',
        ]);

        $matricule = strtoupper(trim($request->input('matricule')));
        $found = $this->findStudentByMatricule($matricule);

        if (!$found) {
            return response()->json([
                'message' => 'Matricule introuvable. Veuillez vérifier votre saisie.',
            ], 404);
        }

        return DB::transaction(function () use ($request, $found, $matricule) {
            $metadata = [
                'payment_method' => $request->input('payment_method', 'manual'),
                'whatsapp'       => $request->input('whatsapp'),
                'source'         => $found['source'],
                'link_id'        => $request->input('link_id'),
            ];

            $fileFields = ['demande_manuscrite', 'acte_naissance', 'quittance'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $path = $request->file($field)->store('service-requests/' . $matricule, 'public');
                    $metadata['files'][$field] = $path;
                }
            }

            $serviceRequest = LegacyStudentServiceRequest::create([
                'legacy_student_id' => $found['legacy_id'] ?? null,
                'matricule'         => $matricule,
                'student_name'      => trim($found['last_name'] . ' ' . $found['first_names']),
                'email'             => $request->input('email'),
                'phone'             => $request->input('whatsapp'),
                'service_type'      => 'bulletin_annuel',
                'service_name'      => 'Bulletin Annuel',
                'filiere_name'      => $found['department'],
                'enrollment_year'   => null,
                'status'            => 'submitted',
                'metadata'          => $metadata,
            ]);

            $reference = 'REF-' . str_pad($serviceRequest->id, 6, '0', STR_PAD_LEFT);

            Log::info('Demande bulletin soumise', [
                'matricule' => $matricule,
                'source'    => $found['source'],
                'reference' => $reference,
            ]);

            return response()->json([
                'success'   => true,
                'message'   => 'Votre demande de bulletin a été soumise avec succès.',
                'reference' => $reference,
            ], 201);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT : GET /api/attestations/demandes/suivi
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Suivi d'une demande par sa référence.
     * GET /api/attestations/demandes/suivi?reference=REF-000001
     */
    public function suiviDemande(Request $request): JsonResponse
    {
        $reference = strtoupper(trim($request->query('reference', '')));

        if (empty($reference)) {
            return response()->json(['message' => 'La référence est requise.'], 400);
        }

        // Extraire le numéro ID de la référence (REF-000001 → 1)
        $id = (int) preg_replace('/[^0-9]/', '', $reference);

        if (!$id) {
            return response()->json(['message' => 'Référence invalide.'], 400);
        }

        $serviceRequest = LegacyStudentServiceRequest::find($id);

        if (!$serviceRequest) {
            return response()->json(['message' => 'Aucune demande trouvée avec cette référence.'], 404);
        }

        $statusLabels = [
            'submitted'       => 'En cours de traitement',
            'pending'         => 'En attente de validation',
            'approved'        => 'Approuvée',
            'ready_for_pickup'=> 'Prête à retirer',
            'picked_up'       => 'Retirée',
            'rejected'        => 'Rejetée',
        ];

        // Découpage du nom si possible
        $nameParts = explode(' ', trim($serviceRequest->student_name ?? ''), 2);
        $lastName = $nameParts[0] ?? '';
        $firstNames = $nameParts[1] ?? '';

        return response()->json([
            'reference'        => 'REF-' . str_pad($serviceRequest->id, 6, '0', STR_PAD_LEFT),
            'type'             => $serviceRequest->service_type,
            'service_type'     => $serviceRequest->service_type,
            'service_name'     => $serviceRequest->service_name,
            'status'           => $serviceRequest->status,
            'status_label'     => $statusLabels[$serviceRequest->status] ?? $serviceRequest->status,
            'email'            => $serviceRequest->email,
            'submitted_at'     => $serviceRequest->created_at?->toISOString(),
            'processed_at'     => $serviceRequest->processed_at?->toISOString(),
            'rejected_reason'  => $serviceRequest->rejection_reason,
            'rejection_reason' => $serviceRequest->rejection_reason,
            'student'          => [
                'last_name'     => $lastName,
                'first_names'   => $firstNames,
                'matricule'     => $serviceRequest->matricule,
                'department'    => $serviceRequest->filiere_name,
                'academic_year' => $serviceRequest->enrollment_year ? "{$serviceRequest->enrollment_year}-" . ($serviceRequest->enrollment_year + 1) : null,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT : GET /api/attestations/demandes/complement/find
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recherche d'une demande de complément de dossier par reference ou matricule.
     * GET /api/attestations/demandes/complement/find?reference=...  ou  ?matricule=...
     */
    public function findComplement(Request $request): JsonResponse
    {
        $reference = strtoupper(trim($request->query('reference', '')));
        $matricule = strtoupper(trim($request->query('matricule', '')));

        if (empty($reference) && empty($matricule)) {
            return response()->json(['message' => 'La référence ou le matricule est requis.'], 400);
        }

        $query = LegacyStudentServiceRequest::where('service_type', 'complement_dossier');

        if ($reference) {
            $id = (int) preg_replace('/[^0-9]/', '', $reference);
            $query->where('id', $id);
        } elseif ($matricule) {
            $query->where('matricule', $matricule);
        }

        $request = $query->orderByDesc('created_at')->first();

        if (!$request) {
            return response()->json(['message' => 'Aucune demande de complément trouvée.'], 404);
        }

        return response()->json([
            'success'      => true,
            'id'           => $request->id,
            'reference'    => 'REF-' . str_pad($request->id, 6, '0', STR_PAD_LEFT),
            'matricule'    => $request->matricule,
            'student_name' => $request->student_name,
            'status'       => $request->status,
            'service_type' => $request->service_type,
            'submitted_at' => $request->created_at?->toISOString(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINT : POST /api/attestations/demandes/complement
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Soumettre un complément de dossier.
     * POST /api/attestations/demandes/complement
     */
    public function submitComplement(Request $request): JsonResponse
    {
        $request->validate([
            'matricule' => 'required|string',
            'email'     => 'required|email',
        ]);

        $matricule = strtoupper(trim($request->input('matricule')));
        $found = $this->findStudentByMatricule($matricule);

        if (!$found) {
            return response()->json(['message' => 'Matricule introuvable.'], 404);
        }

        return DB::transaction(function () use ($request, $found, $matricule) {
            $metadata = ['source' => $found['source']];

            if ($request->hasFile('document') && $request->file('document')->isValid()) {
                $path = $request->file('document')->store('service-requests/complements/' . $matricule, 'public');
                $metadata['files']['document'] = $path;
            }

            $serviceRequest = LegacyStudentServiceRequest::create([
                'legacy_student_id' => $found['legacy_id'] ?? null,
                'matricule'         => $matricule,
                'student_name'      => trim($found['last_name'] . ' ' . $found['first_names']),
                'email'             => $request->input('email'),
                'phone'             => $request->input('phone', ''),
                'service_type'      => 'complement_dossier',
                'service_name'      => 'Complément de Dossier',
                'filiere_name'      => $found['department'],
                'status'            => 'submitted',
                'metadata'          => $metadata,
            ]);

            $reference = 'REF-' . str_pad($serviceRequest->id, 6, '0', STR_PAD_LEFT);

            return response()->json([
                'success'   => true,
                'message'   => 'Votre complément de dossier a été soumis avec succès.',
                'reference' => $reference,
            ], 201);
        });
    }
}

<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Modules\Inscription\Models\InformationCorrectionRequest;
use App\Modules\Inscription\Services\InformationCorrectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class InformationCorrectionController
{
    public function __construct(
        private InformationCorrectionService $service
    ) {}

    // ─── Publiques (site vitrine) ──────────────────────────────────────────────

    /**
     * Rechercher un étudiant et retourner ses informations modifiables.
     * POST /api/inscription/corrections/lookup
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id_number' => ['required', 'string', 'max:50'],
        ]);

        try {
            $studentData = $this->service->lookupStudent($validated['student_id_number']);
            return response()->json(['data' => $studentData]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * Soumettre une demande de correction.
     * POST /api/inscription/corrections
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id_number'        => ['required', 'string', 'max:50'],
            'suggested_values'         => ['required', 'array', 'min:1'],
            'suggested_values.last_name'   => ['sometimes', 'string', 'max:100'],
            'suggested_values.first_names' => ['sometimes', 'string', 'max:200'],
            'suggested_values.email'       => ['sometimes', 'email', 'max:255'],
            'suggested_values.contacts'    => ['sometimes', 'array', 'min:1'],
            'suggested_values.contacts.*'  => ['string', 'max:20'],
            'justification'            => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $correctionRequest = $this->service->submitRequest(
                matricule: $validated['student_id_number'],
                suggestedValues: $validated['suggested_values'],
                justification: $validated['justification'] ?? null,
            );

            return response()->json([
                'message' => 'Votre demande de correction a été soumise avec succès. Vous serez notifié par email une fois traitée.',
                'data'    => ['id' => $correctionRequest->id],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur soumission correction: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Consulter le statut des demandes d'un étudiant.
     * GET /api/inscription/corrections/status/{matricule}
     */
    public function studentStatus(string $matricule): JsonResponse
    {
        $requests = $this->service->getStudentRequests($matricule);

        return response()->json([
            'data' => $requests->map(fn($r) => [
                'id'               => $r->id,
                'status'           => $r->status,
                'suggested_values' => $r->suggested_values,
                'rejection_reason' => $r->rejection_reason,
                'reviewed_at'      => $r->reviewed_at?->format('d/m/Y à H:i'),
                'created_at'       => $r->created_at->format('d/m/Y à H:i'),
            ]),
        ]);
    }

    // ─── Admin (protégées Sanctum) ─────────────────────────────────────────────

    /**
     * Lister toutes les demandes (avec filtres).
     * GET /api/inscription/corrections
     */
    public function index(Request $request): JsonResponse
    {
        $requests = $this->service->listRequests([
            'status'            => $request->query('status'),
            'student_id_number' => $request->query('student_id_number'),
        ]);

        return response()->json([
            'data' => $requests->map(fn($r) => [
                'id'               => $r->id,
                'student_id_number' => $r->student_id_number,
                'current_values'   => $r->current_values,
                'suggested_values' => $r->suggested_values,
                'changed_fields'   => $r->changed_fields,
                'justification'    => $r->justification,
                'status'           => $r->status,
                'rejection_reason' => $r->rejection_reason,
                'reviewed_by'      => $r->reviewer ? [
                    'id'   => $r->reviewer->id,
                    'name' => $r->reviewer->first_name . ' ' . $r->reviewer->last_name,
                ] : null,
                'reviewed_at'      => $r->reviewed_at?->format('d/m/Y à H:i'),
                'created_at'       => $r->created_at->format('d/m/Y à H:i'),
            ]),
            'counts' => [
                'pending'  => $requests->where('status', 'pending')->count(),
                'approved' => $requests->where('status', 'approved')->count(),
                'rejected' => $requests->where('status', 'rejected')->count(),
            ],
        ]);
    }

    /**
     * Approuver une demande.
     * PATCH /api/inscription/corrections/{id}/approve
     */
    public function approve(string $id, Request $request): JsonResponse
    {
        $correctionRequest = InformationCorrectionRequest::findOrFail($id);

        if ($correctionRequest->status !== 'pending') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        try {
            $this->service->approve($correctionRequest, $request->user()->id);
            return response()->json(['message' => 'Demande approuvée. Les informations ont été mises à jour.']);
        } catch (\Exception $e) {
            Log::error('Erreur approbation correction: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Rejeter une demande.
     * PATCH /api/inscription/corrections/{id}/reject
     */
    public function reject(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $correctionRequest = InformationCorrectionRequest::findOrFail($id);

        if ($correctionRequest->status !== 'pending') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        try {
            $this->service->reject(
                correctionRequest: $correctionRequest,
                reviewerId: $request->user()->id,
                rejectionReason: $validated['rejection_reason'],
            );
            return response()->json(['message' => 'Demande rejetée.']);
        } catch (\Exception $e) {
            Log::error('Erreur rejet correction: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Attestation\Services\DemandeSubmissionService;
use App\Exceptions\BusinessException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CORRECTIF (v2) — Thin Controller fidèle au DemandeController réel
 *
 * Toute la logique (validation métier, stockage, quittance PDF,
 * insertion, notification) est déplacée vers DemandeSubmissionService.
 * Les validations de FORME (request->validate(...)) restent ici,
 * exactement comme dans le contrôleur source.
 */
class DemandeController extends Controller
{
    public function __construct(
        private readonly DemandeSubmissionService $submissionService,
    ) {}

    // ══════════════════════════════════════════════════════════════════════════
    // POST /api/attestations/demandes
    // ══════════════════════════════════════════════════════════════════════════

    public function storeDemande(Request $request): JsonResponse
    {
        $request->validate([
            'matricule'         => 'required|string|regex:/^[a-zA-Z0-9\-]+$/',
            'type'              => 'required|in:attestation_passage,attestation_definitive,attestation_inscription',
            'email'             => 'required|email|max:150',
            'whatsapp'          => 'required|string|max:30|regex:/^[0-9\+\s\-]+$/',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9\-]+$/',
            'demande_manuscrite'=> 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'acte_naissance'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'attestation_succes_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'quittance'         => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'recu_paiement'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'bulletin'          => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        try {
            $result = $this->submissionService->submitAttestationRequest($request);

            return response()->json([
                'message'   => 'Demande soumise avec succès.',
                'reference' => $result['reference'],
            ], 201);

        } catch (BusinessException $e) {
            $body = ['message' => $e->getMessage()];
            return response()->json($body, $e->getStatusCode());
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST /api/attestations/bulletins
    // ══════════════════════════════════════════════════════════════════════════

    public function storeBulletinDemande(Request $request): JsonResponse
    {
        $request->validate([
            'link_id'           => 'required|integer|exists:student_pending_student,id',
            'type'              => 'required|in:bulletin_annuel',
            'email'             => 'required|email|max:150',
            'whatsapp'          => 'required|string|max:30|regex:/^[0-9\+\s\-]+$/',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9\-]+$/',
            'demande_manuscrite'=> 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'acte_naissance'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'quittance'         => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        try {
            $result = $this->submissionService->submitBulletinRequest($request);

            return response()->json([
                'message'   => 'Demande de bulletin soumise avec succès.',
                'reference' => $result['reference'],
            ], 201);

        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GET /api/attestations/demandes/suivi
    // ══════════════════════════════════════════════════════════════════════════

    public function suiviDemande(Request $request): JsonResponse
    {
        $request->validate(['reference' => 'required|string']);

        try {
            $data = $this->submissionService->getSuivi($request->reference);
            return response()->json($data);
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }
}

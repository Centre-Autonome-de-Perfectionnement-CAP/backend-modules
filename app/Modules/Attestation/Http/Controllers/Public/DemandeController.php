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
            // Matricules des anciens étudiants (avant 2023) : formats plus variés.
            'matricule'         => 'required|string|max:50|regex:/^[a-zA-Z0-9\-\/\._ ]+$/',
            'type'              => 'required|in:attestation_passage,attestation_definitive,attestation_inscription',
            'email'             => 'required|email|max:150',
            'whatsapp'          => 'required|string|max:30|regex:/^[0-9\+\s\-]+$/',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9\-]+$/',
            'demande_manuscrite'=> 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
            'acte_naissance'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
            'attestation_succes_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
            'quittance'         => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
            'recu_paiement'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
            'bulletin'          => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
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
            // Étudiant inscrit : link_id. Ancien étudiant (D3) : legacy_matricule
            // + année choisie (AAAA-AAAA), vérifiée par la secrétaire.
            'link_id'           => 'required_without:legacy_matricule|nullable|integer|exists:student_pending_student,id',
            'legacy_matricule'  => 'required_without:link_id|nullable|string|max:50|regex:/^[a-zA-Z0-9\-\/\._ ]+$/',
            'bulletin_year'     => 'required_with:legacy_matricule|nullable|string|max:9|regex:/^\d{4}-\d{4}$/',
            'type'              => 'required|in:bulletin_annuel',
            'email'             => 'required|email|max:150',
            'whatsapp'          => 'required|string|max:30|regex:/^[0-9\+\s\-]+$/',
            'payment_method'    => 'nullable|in:manual,tresor_online',
            'payment_reference' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9\-]+$/',
            'demande_manuscrite'=> 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
            'acte_naissance'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
            'quittance'         => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif,doc,docx|max:5120',
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
        $request->validate(['reference' => 'required|string|max:50']);

        // FUSION develop/main : références REF-xxxxxx créées par l'ancien système
        // provisoire de main (table legacy_student_service_requests) — restent
        // consultables, en lecture seule, via le contrôleur d'origine.
        if (self::isLegacyServiceReference($request->reference)) {
            return app(\App\Modules\LegacyStudent\Http\Controllers\StudentServiceController::class)
                ->suiviDemande($request);
        }

        try {
            $data = $this->submissionService->getSuivi($request->reference);
            return response()->json($data);
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    /**
     * Référence de l'ancien système provisoire (REF-000123).
     */
    public static function isLegacyServiceReference(?string $reference): bool
    {
        return (bool) preg_match('/^REF-\d{1,10}$/', strtoupper(trim((string) $reference)));
    }
}

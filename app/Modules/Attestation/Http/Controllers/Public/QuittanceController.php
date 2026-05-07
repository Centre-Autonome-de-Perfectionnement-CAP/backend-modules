<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log, Mail};

/**
 * Génération et envoi de quittances Trésor Public (site vitrine)
 *
 * POST /api/attestations/quittance/generate
 *
 * Appelé par le frontend après confirmation de paiement Trésor.
 * Génère un PDF de quittance et l'envoie par email en pièce jointe.
 */
class QuittanceController extends Controller
{
    public function generateAndSendQuittance(Request $request): JsonResponse
    {
        $request->validate([
            'quittanceNumber'  => 'required|string',
            'montant'          => 'required|integer|min:1',
            'motif'            => 'required|string',
            'nomEtudiant'      => 'required|string',
            'matricule'        => 'required|string',
            'email'            => 'required|email',
            'referenceDemande' => 'required|string',
            'paidAt'           => 'required|string',
            'simulation'       => 'boolean',
        ]);

        try {
            $datePaiement = Carbon::parse($request->paidAt)
                ->setTimezone('Africa/Porto-Novo')
                ->translatedFormat('d F Y à H\hi');

            $pdfContent  = $this->buildPdf($request, $datePaiement);
            $pdfFilename = 'quittance-' . $request->quittanceNumber . '.pdf';

            $this->sendQuittanceEmail($request, $pdfContent, $pdfFilename, $datePaiement);

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'         => 'Quittance générée et envoyée par email.',
                    'pdfBase64'       => base64_encode($pdfContent),
                    'pdfFilename'     => $pdfFilename,
                    'quittanceNumber' => $request->quittanceNumber,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur génération quittance PDF : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la quittance.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers privés
    // ──────────────────────────────────────────────────────────────────────────

    private function buildPdf(Request $request, string $datePaiement): string
    {
        return Pdf::loadView('core::pdfs.quittance-tresor', [
            'quittanceNumber'  => $request->quittanceNumber,
            'montant'          => $request->montant,
            'motif'            => $request->motif,
            'nomEtudiant'      => strtoupper($request->nomEtudiant),
            'matricule'        => strtoupper($request->matricule),
            'referenceDemande' => strtoupper($request->referenceDemande),
            'datePaiement'     => $datePaiement,
            'simulation'       => $request->boolean('simulation', true),
        ])->setPaper('A4', 'portrait')->output();
    }

    private function sendQuittanceEmail(Request $request, string $pdfContent, string $pdfFilename, string $datePaiement): void
    {
        Mail::send(
            'core::emails.attestation-confirmation',
            [
                'reference'     => $request->referenceDemande,
                'type'          => 'paiement_tresor',
                'studentName'   => $request->matricule,
                'submittedAt'   => $datePaiement,
                'paymentMethod' => 'tresor_online',
                'paymentRef'    => $request->quittanceNumber,
            ],
            fn($message) => $message
                ->to($request->email)
                ->subject("Votre quittance de paiement — {$request->quittanceNumber}")
                ->attachData($pdfContent, $pdfFilename, ['mime' => 'application/pdf'])
        );
    }
}

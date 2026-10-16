<?php

namespace App\Modules\Attestation\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    public function __construct(
        private readonly \App\Modules\Attestation\Services\WhatsAppNotificationService $whatsApp,
        private readonly \App\Modules\Core\Services\Notifier $notifier,
    ) {}

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

            // Email (PDF joint) + WhatsApp (PDF) : asynchrones et indépendants.
            // Le PDF est persisté sur le disque privé pour que les workers
            // puissent le joindre après la réponse HTTP.
            $pdfPath = $this->storePdf($request->quittanceNumber, $pdfContent);
            $this->queueQuittanceEmail($request, $pdfPath, $pdfFilename, $datePaiement);

            $phone = $this->findStudentPhone((string) $request->matricule);
            if ($phone && $pdfPath) {
                $this->notifier->whatsappFile(
                    $phone,
                    'local',
                    $pdfPath,
                    $pdfFilename,
                    $this->whatsApp->quittanceMessage($request->quittanceNumber, $request->referenceDemande, $request->nomEtudiant),
                    "attestation-quittance:{$request->quittanceNumber}",
                    'Attestation',
                );
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'         => 'Quittance générée. Elle vous est transmise par email (et WhatsApp si votre numéro est connu).',
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

    private function storePdf(string $quittanceNumber, string $pdfContent): ?string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', $quittanceNumber);
        $path = 'quittances/tresor/' . now()->format('Y/m') . "/quittance-{$safe}-" . \Illuminate\Support\Str::random(8) . '.pdf';

        try {
            \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pdfContent);
            return $path;
        } catch (\Throwable $e) {
            Log::error('Quittance : stockage du PDF impossible', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function queueQuittanceEmail(Request $request, ?string $pdfPath, string $pdfFilename, string $datePaiement): void
    {
        $this->notifier->email(
            $request->email,
            "Votre quittance de paiement — {$request->quittanceNumber}",
            'core::emails.attestation-confirmation',
            [
                'reference'     => $request->referenceDemande,
                'type'          => 'paiement_tresor',
                'studentName'   => $request->matricule,
                'submittedAt'   => $datePaiement,
                'paymentMethod' => 'tresor_online',
                'paymentRef'    => $request->quittanceNumber,
            ],
            $pdfPath ? [['disk' => 'local', 'path' => $pdfPath, 'name' => $pdfFilename]] : [],
        );
    }

    /**
     * CORRECTIF : l'ancienne recherche lisait personalInformation->phone,
     * attribut inexistant (le numéro est dans "contacts") : aucun WhatsApp
     * n'était jamais envoyé. Couvre aussi les anciens étudiants déclarés.
     */
    private function findStudentPhone(string $matricule): ?string
    {
        $matricule = strtoupper(trim($matricule));

        $student = \App\Modules\Inscription\Models\Student::where('student_id_number', $matricule)->first();
        if ($student) {
            $link = \App\Modules\Inscription\Models\StudentPendingStudent::where('student_id', $student->id)
                ->with('pendingStudent.personalInformation')
                ->latest('id')
                ->first();
            $phone = $this->notifier->phoneFromContacts($link?->pendingStudent?->personalInformation?->contacts);
            if ($phone) {
                return $phone;
            }
        }

        $legacy = \App\Modules\LegacyStudent\Models\LegacyStudent::where('matricule', $matricule)
            ->where('status', '!=', 'rejected')
            ->first();

        return $this->notifier->phoneFromContacts($legacy?->phone);
    }
}

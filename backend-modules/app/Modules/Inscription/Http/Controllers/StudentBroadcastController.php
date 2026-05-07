<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Models\Department;
use App\Modules\Core\Services\BroadcastService;
use App\Modules\Inscription\Services\StudentListPdfService;
use App\Modules\Core\Mail\WhatsAppGroupInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class StudentBroadcastController extends Controller
{
    use ApiResponse;

    protected $broadcastService;
    protected $pdfService;

    public function __construct(BroadcastService $broadcastService, StudentListPdfService $pdfService)
    {
        $this->broadcastService = $broadcastService;
        $this->pdfService = $pdfService;
    }

    /**
     * Envoie un message avec lien WhatsApp aux étudiants d'une filière
     */
    public function sendWhatsAppInvitation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'message' => 'required|string|max:2000',
            'cohort' => 'nullable|string',
        ]);

        try {
            // Récupérer la filière
            $department = Department::with('cycle')->findOrFail($data['department_id']);

            // Vérifier que le lien WhatsApp est défini
            if (!$department->whatsapp_link) {
                return $this->errorResponse(
                    'Aucun lien WhatsApp n\'est défini pour cette filière. Veuillez d\'abord configurer le lien dans le module RH.',
                    400
                );
            }

            \Log::info('Envoi invitation WhatsApp', [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'cohort' => $data['cohort'] ?? 'all',
            ]);

            // Récupérer les étudiants de la filière
            $criteria = [
                'department_ids' => [$department->id],
            ];

            if (isset($data['cohort']) && $data['cohort']) {
                $criteria['cohort'] = $data['cohort'];
            }

            $students = $this->broadcastService->getStudents($criteria);

            if ($students->isEmpty()) {
                return $this->errorResponse('Aucun étudiant trouvé pour cette filière', 404);
            }

            // Générer le PDF avec la liste des étudiants
            $pdfPath = $this->pdfService->generateStudentListPdf($students->toArray(), $department->name);

            \Log::info('PDF liste étudiants généré', [
                'department_id' => $department->id,
                'pdf_path' => $pdfPath,
                'total_students' => $students->count(),
            ]);

            // Générer un ID unique pour ce broadcast
            $broadcastId = uniqid('whatsapp_broadcast_', true);

            // Initialiser le statut dans le cache
            \Cache::put("whatsapp_broadcast_status_{$broadcastId}", [
                'total_students' => $students->count(),
                'emails_sent' => 0,
                'emails_failed' => 0,
                'status' => 'queued',
                'started_at' => now(),
            ], now()->addHours(24));

            // Diviser les étudiants en chunks de 50 pour éviter les jobs trop lourds
            $chunks = $students->chunk(50);
            
            foreach ($chunks as $chunk) {
                // Dispatcher le job en queue
                \App\Modules\Inscription\Jobs\BroadcastWhatsAppInvitationJob::dispatch(
                    $chunk->toArray(),
                    $data['message'],
                    $department->whatsapp_link,
                    $department->name,
                    $broadcastId,
                    $pdfPath // Passer le chemin du PDF
                )->onQueue('emails');
            }

            \Log::info('Invitation WhatsApp lancée', [
                'broadcast_id' => $broadcastId,
                'department_id' => $department->id,
                'total_students' => $students->count(),
                'chunks' => $chunks->count(),
            ]);

            return $this->successResponse([
                'broadcast_id' => $broadcastId,
                'total_students' => $students->count(),
                'status' => 'queued',
                'message' => 'La diffusion a été mise en file d\'attente. Les emails seront envoyés progressivement.',
            ], "Diffusion lancée pour {$students->count()} étudiant(s)");

        } catch (\Exception $e) {
            \Log::error('Erreur envoi invitation WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur lors de l\'envoi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Récupère le statut d'un broadcast WhatsApp
     */
    public function getBroadcastStatus(string $broadcastId): JsonResponse
    {
        $status = \Cache::get("whatsapp_broadcast_status_{$broadcastId}");

        if (!$status) {
            return $this->errorResponse('Broadcast non trouvé', 404);
        }

        return $this->successResponse($status);
    }
}

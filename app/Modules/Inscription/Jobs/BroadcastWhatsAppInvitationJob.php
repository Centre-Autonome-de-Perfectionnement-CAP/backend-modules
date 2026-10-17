<?php

namespace App\Modules\Inscription\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Modules\Core\Mail\WhatsAppGroupInvitation;

class BroadcastWhatsAppInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // < retry_after (90 s) de la file : sinon le job est relancé en double
    // pendant qu'il tourne encore (emails envoyés deux fois). Lots de 20.
    public $timeout = 85;
    public $tries = 3; // Réessayer 3 fois en cas d'échec

    protected $students;
    protected $message;
    protected $whatsappLink;
    protected $departmentName;
    protected $broadcastId;
    protected $pdfPath;

    /**
     * Create a new job instance.
     */
    public function __construct($students, $message, $whatsappLink, $departmentName, $broadcastId, $pdfPath = null)
    {
        $this->students = $students;
        $this->message = $message;
        $this->whatsappLink = $whatsappLink;
        $this->departmentName = $departmentName;
        $this->broadcastId = $broadcastId;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sentCount = 0;
        $failedCount = 0;
        $failedEmails = [];
        $skippedCount = 0;
        $notifier = app(\App\Modules\Core\Services\Notifier::class);
        $waMessage = \App\Modules\Core\Support\WhatsAppText::compose("Groupe WhatsApp {$this->departmentName}", [
            trim(strip_tags((string) $this->message)),
            $this->whatsappLink ? "_Rejoindre le groupe :_\n{$this->whatsappLink}" : '',
        ]);

        foreach ($this->students as $student) {
            Log::debug('Processing student in WhatsApp broadcast job', [
                'broadcast_id' => $this->broadcastId,
                'student_id' => $student['id'] ?? 'unknown',
                'has_personal_information' => isset($student['personal_information']) && $student['personal_information'] !== null,
            ]);

            $personalInfo = $student['personal_information'] ?? null;
            
            if ($personalInfo && !empty($personalInfo)) {
                // Si c'est un tableau, accéder comme un tableau
                $email = is_array($personalInfo) ? ($personalInfo['email'] ?? null) : ($personalInfo->email ?? null);

                // WhatsApp : job indépendant, cadencé, publié une seule fois.
                if ($this->attempts() <= 1) {
                    $contacts = is_array($personalInfo) ? ($personalInfo['contacts'] ?? null) : ($personalInfo->contacts ?? null);
                    $notifier->whatsapp(
                        $notifier->phoneFromContacts($contacts),
                        $waMessage,
                        "invitation-groupe:{$this->broadcastId}:" . ($student['id'] ?? ''),
                        'Inscription',
                        bulk: true,
                    );
                }
                
                if ($email) {
                    try {
                        Mail::to($email)->send(
                            new WhatsAppGroupInvitation($this->message, $this->whatsappLink, $this->departmentName, $this->pdfPath)
                        );
                        $sentCount++;
                        
                        Log::info('Email invitation WhatsApp envoyé', [
                            'broadcast_id' => $this->broadcastId,
                            'student_id' => $student['id'] ?? 'unknown',
                            'email' => $email,
                        ]);
                    } catch (\Exception $e) {
                        $failedCount++;
                        $failedEmails[] = $email;
                        
                        Log::error('Erreur envoi email invitation WhatsApp', [
                            'broadcast_id' => $this->broadcastId,
                            'student_id' => $student['id'] ?? 'unknown',
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    $skippedCount++;
                    Log::warning('Student sans email', [
                        'broadcast_id' => $this->broadcastId,
                        'student_id' => $student['id'] ?? 'unknown',
                    ]);
                }
            } else {
                $skippedCount++;
                Log::warning('Student sans personal_information', [
                    'broadcast_id' => $this->broadcastId,
                    'student_id' => $student['id'] ?? 'unknown',
                ]);
            }
        }

        // Logger le résumé
        Log::info('Job WhatsApp broadcast terminé', [
            'broadcast_id' => $this->broadcastId,
            'total_students' => count($this->students),
            'emails_sent' => $sentCount,
            'emails_failed' => $failedCount,
            'failed_emails' => $failedEmails,
        ]);

        // Mettre à jour le statut dans le cache
        $cacheKey = "whatsapp_broadcast_status_{$this->broadcastId}";
        $currentStatus = \Cache::get($cacheKey, [
            'total_students' => 0,
            'emails_sent' => 0,
            'emails_failed' => 0,
            'status' => 'processing',
        ]);

        $emailsSent   = $currentStatus['emails_sent'] + $sentCount;
        $emailsFailed = $currentStatus['emails_failed'] + $failedCount;
        $skipped      = ($currentStatus['skipped'] ?? 0) + $skippedCount;
        // CORRECTIF : "completed" (et suppression du PDF) seulement quand tous
        // les lots sont passés — avant, le 1er lot supprimait le PDF des suivants.
        $done = $emailsSent + $emailsFailed + $skipped >= (int) ($currentStatus['total_students'] ?? 0);

        \Cache::put($cacheKey, array_merge($currentStatus, [
            'emails_sent' => $emailsSent,
            'emails_failed' => $emailsFailed,
            'skipped' => $skipped,
            'status' => $done ? 'completed' : 'processing',
            'completed_at' => $done ? now() : null,
        ]), now()->addHours(24));

        // Nettoyer le PDF temporaire après l'envoi (seulement pour le dernier chunk)
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            // Vérifier si c'est le dernier job en vérifiant le statut
            $status = \Cache::get($cacheKey);
            if ($status && $status['status'] === 'completed') {
                try {
                    unlink($this->pdfPath);
                    Log::info('PDF temporaire supprimé', [
                        'broadcast_id' => $this->broadcastId,
                        'pdf_path' => $this->pdfPath,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Impossible de supprimer le PDF temporaire', [
                        'broadcast_id' => $this->broadcastId,
                        'pdf_path' => $this->pdfPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job WhatsApp broadcast échoué complètement', [
            'broadcast_id' => $this->broadcastId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mettre à jour le statut en échec
        $cacheKey = "whatsapp_broadcast_status_{$this->broadcastId}";
        $currentStatus = \Cache::get($cacheKey, []);
        
        \Cache::put($cacheKey, array_merge($currentStatus, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'failed_at' => now(),
        ]), now()->addHours(24));
    }
}

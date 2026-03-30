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

    public $timeout = 300; // 5 minutes max par job
    public $tries = 3; // Réessayer 3 fois en cas d'échec

    protected $students;
    protected $message;
    protected $whatsappLink;
    protected $departmentName;
    protected $broadcastId;

    /**
     * Create a new job instance.
     */
    public function __construct($students, $message, $whatsappLink, $departmentName, $broadcastId)
    {
        $this->students = $students;
        $this->message = $message;
        $this->whatsappLink = $whatsappLink;
        $this->departmentName = $departmentName;
        $this->broadcastId = $broadcastId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sentCount = 0;
        $failedCount = 0;
        $failedEmails = [];

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
                
                if ($email) {
                    try {
                        Mail::to($email)->send(
                            new WhatsAppGroupInvitation($this->message, $this->whatsappLink, $this->departmentName)
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
                    Log::warning('Student sans email', [
                        'broadcast_id' => $this->broadcastId,
                        'student_id' => $student['id'] ?? 'unknown',
                    ]);
                }
            } else {
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

        \Cache::put($cacheKey, [
            'total_students' => $currentStatus['total_students'],
            'emails_sent' => $currentStatus['emails_sent'] + $sentCount,
            'emails_failed' => $currentStatus['emails_failed'] + $failedCount,
            'status' => 'completed',
            'completed_at' => now(),
        ], now()->addHours(24));
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

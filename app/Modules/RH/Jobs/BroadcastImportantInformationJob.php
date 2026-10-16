<?php

namespace App\Modules\RH\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Modules\Core\Mail\ImportantInformationNotification;

class BroadcastImportantInformationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // < retry_after (90 s) de la file : sinon le job est relancé en double
    // pendant qu'il tourne encore (emails envoyés deux fois). Lots de 20.
    public $timeout = 85;
    public $tries = 3; // Réessayer 3 fois en cas d'échec

    protected $students;
    protected $informationData;
    protected $fileUrls; // Changé de $fileUrl à $fileUrls (array)
    protected $broadcastId;

    /**
     * Create a new job instance.
     */
    public function __construct($students, $informationData, $fileUrls, $broadcastId)
    {
        $this->students = $students;
        $this->informationData = $informationData;
        $this->fileUrls = $fileUrls;
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
        $skippedCount = 0;
        $notifier = app(\App\Modules\Core\Services\Notifier::class);
        $waMessage = $this->buildWhatsAppMessage();

        foreach ($this->students as $student) {
            // Debug: logger les données reçues
            Log::debug('Processing student in broadcast job', [
                'broadcast_id' => $this->broadcastId,
                'student_id' => $student['id'] ?? 'unknown',
                'has_personal_information' => isset($student['personal_information']) && $student['personal_information'] !== null,
                'personal_info_type' => isset($student['personal_information']) && $student['personal_information'] !== null ? gettype($student['personal_information']) : 'not set',
            ]);

            $personalInfo = $student['personal_information'] ?? null;
            
            // Vérifier que personalInfo n'est pas null
            if ($personalInfo && !empty($personalInfo)) {
                // Si c'est un tableau, accéder comme un tableau
                $email = is_array($personalInfo) ? ($personalInfo['email'] ?? null) : ($personalInfo->email ?? null);

                // WhatsApp : job indépendant et cadencé (diffusion de masse),
                // publié une seule fois même si ce job est retenté.
                if ($this->attempts() <= 1) {
                    $contacts = is_array($personalInfo) ? ($personalInfo['contacts'] ?? null) : ($personalInfo->contacts ?? null);
                    $notifier->whatsapp(
                        $notifier->phoneFromContacts($contacts),
                        $waMessage,
                        "info-importante:{$this->broadcastId}:" . ($student['id'] ?? ''),
                        'RH',
                        bulk: true,
                    );
                }
                
                if ($email) {
                    try {
                        Mail::to($email)->send(
                            new ImportantInformationNotification($this->informationData, $this->fileUrls)
                        );
                        $sentCount++;
                        
                        Log::info('Email information importante envoyé', [
                            'broadcast_id' => $this->broadcastId,
                            'student_id' => $student['id'] ?? 'unknown',
                            'email' => $email,
                        ]);
                    } catch (\Exception $e) {
                        $failedCount++;
                        $failedEmails[] = $email;
                        
                        Log::error('Erreur envoi email information importante', [
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
        Log::info('Job broadcast terminé', [
            'broadcast_id' => $this->broadcastId,
            'total_students' => count($this->students),
            'emails_sent' => $sentCount,
            'emails_failed' => $failedCount,
            'failed_emails' => $failedEmails,
        ]);

        // Mettre à jour le statut dans le cache
        $cacheKey = "broadcast_status_{$this->broadcastId}";
        $currentStatus = \Cache::get($cacheKey, [
            'total_students' => 0,
            'emails_sent' => 0,
            'emails_failed' => 0,
            'status' => 'processing',
        ]);

        $emailsSent   = $currentStatus['emails_sent'] + $sentCount;
        $emailsFailed = $currentStatus['emails_failed'] + $failedCount;
        $skipped      = ($currentStatus['skipped'] ?? 0) + $skippedCount;
        // CORRECTIF : "completed" seulement quand tous les lots sont passés
        // (avant, le premier lot terminé marquait toute la diffusion finie).
        $done = $emailsSent + $emailsFailed + $skipped >= (int) ($currentStatus['total_students'] ?? 0);

        \Cache::put($cacheKey, array_merge($currentStatus, [
            'emails_sent' => $emailsSent,
            'emails_failed' => $emailsFailed,
            'skipped' => $skipped,
            'status' => $done ? 'completed' : 'processing',
            'completed_at' => $done ? now() : null,
        ]), now()->addHours(24));
    }

    private function buildWhatsAppMessage(): string
    {
        $title = trim((string) ($this->informationData['title'] ?? ''));
        $description = trim(strip_tags((string) ($this->informationData['description'] ?? '')));
        if (mb_strlen($description) > 1200) {
            $description = mb_substr($description, 0, 1200) . '…';
        }
        $link = trim((string) ($this->informationData['link'] ?? ''));

        return "*CAP-EPAC — Information importante*\n*{$title}*\n\n{$description}"
            . ($link !== '' ? "\n\nLien : {$link}" : '')
            . (!empty($this->fileUrls) ? "\n\n_Les pièces jointes vous ont été envoyées par e-mail._" : '');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job broadcast échoué complètement', [
            'broadcast_id' => $this->broadcastId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mettre à jour le statut en échec
        $cacheKey = "broadcast_status_{$this->broadcastId}";
        $currentStatus = \Cache::get($cacheKey, []);
        
        \Cache::put($cacheKey, array_merge($currentStatus, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'failed_at' => now(),
        ]), now()->addHours(24));
    }
}

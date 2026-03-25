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

    public $timeout = 300; // 5 minutes max par job
    public $tries = 3; // Réessayer 3 fois en cas d'échec

    protected $students;
    protected $informationData;
    protected $fileUrl;
    protected $broadcastId;

    /**
     * Create a new job instance.
     */
    public function __construct($students, $informationData, $fileUrl, $broadcastId)
    {
        $this->students = $students;
        $this->informationData = $informationData;
        $this->fileUrl = $fileUrl;
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
            $personalInfo = $student->personalInformation ?? $student['personal_information'] ?? null;
            
            if ($personalInfo && isset($personalInfo->email)) {
                try {
                    Mail::to($personalInfo->email)->send(
                        new ImportantInformationNotification($this->informationData, $this->fileUrl)
                    );
                    $sentCount++;
                    
                    Log::info('Email information importante envoyé', [
                        'broadcast_id' => $this->broadcastId,
                        'student_id' => $student->id ?? $student['id'],
                        'email' => $personalInfo->email,
                    ]);
                } catch (\Exception $e) {
                    $failedCount++;
                    $failedEmails[] = $personalInfo->email;
                    
                    Log::error('Erreur envoi email information importante', [
                        'broadcast_id' => $this->broadcastId,
                        'student_id' => $student->id ?? $student['id'],
                        'email' => $personalInfo->email,
                        'error' => $e->getMessage(),
                    ]);
                }
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

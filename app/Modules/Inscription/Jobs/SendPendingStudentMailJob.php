<?php

namespace App\Modules\Inscription\Jobs;

use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\AcademicPath;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SendPendingStudentMailJob
{
    use Dispatchable, SerializesModels;

    protected $studentData;

    public function __construct(array $studentData)
    {
        $this->studentData = $studentData;
    }

    public function handle()
    {
        $student = PendingStudent::with('personalInformation', 'department')->find($this->studentData['studentId']);
        
        if (!$student) {
            return;
        }

        $mailService = app(\App\Modules\Core\Services\MailService::class);
        
        $opinionCuca = $this->studentData['opinionCuca'] ?? null;
        $opinionCuo = $this->studentData['opinionCuo'] ?? null;
        
        $updateData = [];
        if ($opinionCuca) {
            $updateData['cuca_opinion'] = strtolower($opinionCuca) === 'favorable' ? 'favorable' : 'défavorable';
            $updateData['cuca_comment'] = $this->studentData['commentaireCuca'] ?? null;
        }
        if ($opinionCuo) {
            $updateData['cuo_opinion'] = strtolower($opinionCuo) === 'favorable' ? 'favorable' : 'défavorable';
            $updateData['cuo_comment'] = $this->studentData['commentaireCuo'] ?? null;
        }
        
        // Mettre à jour uniquement les opinions (pas le statut)
        if (!empty($updateData)) {
            $student->update($updateData);
            Log::info('Opinions mises à jour', [
                'student_id' => $student->id,
                'opinion_cuca' => $opinionCuca,
                'opinion_cuo' => $opinionCuo
            ]);
        }
        
        $template = $opinionCuca === 'Favorable' || $opinionCuo === 'Favorable' 
            ? 'acceptation-candidature' 
            : 'rejet-candidature';
        
        $mailData = [
            'nom' => $student->personalInformation->last_name,
            'prenoms' => $student->personalInformation->first_names,
            'filiere' => $student->department->name,
            'opinionCuca' => $opinionCuca,
            'commentaireCuca' => $this->studentData['commentaireCuca'] ?? null,
            'opinionCuo' => $opinionCuo,
            'commentaireCuo' => $this->studentData['commentaireCuo'] ?? null,
        ];

        $subject = $template === 'acceptation-candidature' 
            ? 'Acceptation de votre candidature' 
            : 'Décision concernant votre candidature';

        try {
            $mailService->sendWithTemplate(
                $student->personalInformation->email,
                $subject,
                $template,
                $mailData
            );
            
            Log::info('Mail envoyé avec succès', [
                'student_id' => $student->id,
                'email' => $student->personalInformation->email,
                'opinionCuca' => $opinionCuca,
                'opinionCuo' => $opinionCuo,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur envoi mail', ['error' => $e->getMessage()]);
            throw $e;
        }
        
        // Créer Student, StudentPendingStudent et AcademicPath si favorable
        $cycle = strtolower($student->department->cycle->name ?? '');
        $departmentName = strtolower($student->department->name ?? '');
        $isFavorable = false;
        
        // Prépa = cycle Ingénierie ET nom contient "prepa"
        $isPrepa = (str_contains($cycle, 'ingénierie') || str_contains($cycle, 'ingenierie')) && 
                   (str_contains($departmentName, 'prépa') || str_contains($departmentName, 'prepa'));
        
        if ($isPrepa) {
            // Pour Prépa, seule CUCA décide
            $isFavorable = ($opinionCuca && strtolower($opinionCuca) === 'favorable');
        } else {
            // Pour Licence/Master et autres Ingénierie, seule CUO décide
            $isFavorable = ($opinionCuo && strtolower($opinionCuo) === 'favorable');
        }
        
        Log::info('Vérification favorable', [
            'cycle' => $cycle,
            'isFavorable' => $isFavorable,
            'opinionCuca' => $opinionCuca,
            'opinionCuo' => $opinionCuo,
        ]);
        
        if ($isFavorable) {
            try {
                // Vérifier si le student existe déjà
                $existingLink = StudentPendingStudent::where('pending_student_id', $student->id)->first();
                
                Log::info('Vérification existingLink', ['exists' => !is_null($existingLink)]);
                
                if (!$existingLink) {
                    // Utiliser le PendingStudentService pour créer l'étudiant officiel
                    Log::info('Appel de PendingStudentService::createOfficialStudent');
                    $pendingStudentService = app(\App\Modules\Inscription\Services\PendingStudentService::class);
                    $pendingStudentService->changeStatus($student, 'approved');
                }
            } catch (\Exception $e) {
                Log::error('Erreur création Student', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }
    }
    

}

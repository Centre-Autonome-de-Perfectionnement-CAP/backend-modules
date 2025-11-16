<?php

namespace App\Modules\Inscription\Jobs;

use App\Modules\Inscription\Models\PendingStudent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class SendPendingStudentMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 300];

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
        
        // Mettre à jour les opinions et commentaires
        $updateData = [];
        if ($opinionCuca) {
            $updateData['cuca_opinion'] = strtolower($opinionCuca) === 'favorable' ? 'favorable' : 'defavorable';
            $updateData['cuca_comment'] = $this->studentData['commentaireCuca'] ?? null;
        }
        if ($opinionCuo) {
            $updateData['cuo_opinion'] = strtolower($opinionCuo) === 'favorable' ? 'favorable' : 'defavorable';
        }
        
        if (!empty($updateData)) {
            $student->update($updateData);
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

        $mailService->sendWithTemplate(
            $student->personalInformation->email,
            $subject,
            $template,
            $mailData
        );
    }
}

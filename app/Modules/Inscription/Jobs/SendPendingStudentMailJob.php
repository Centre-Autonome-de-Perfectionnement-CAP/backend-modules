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
        
        // Déterminer le nouveau statut selon les règles
        $cycle = strtolower($student->department->cycle->name ?? '');
        $departmentName = strtolower($student->department->name ?? '');
        $newStatus = null;
        
        // Prépa = cycle Ingénierie ET nom contient "prepa"
        $isPrepa = (str_contains($cycle, 'ingénierie') || str_contains($cycle, 'ingenierie')) && 
                   (str_contains($departmentName, 'prépa') || str_contains($departmentName, 'prepa'));
        
        if ($isPrepa) {
            // Pour Prépa, seul CUCA décide
            if ($opinionCuca && strtolower($opinionCuca) === 'favorable') {
                $newStatus = 'approved';
            } elseif ($opinionCuca && strtolower($opinionCuca) !== 'favorable') {
                $newStatus = 'rejected';
            }
        } else {
            // Pour Licence/Master et autres Ingénierie, seul CUO décide
            if ($opinionCuo && strtolower($opinionCuo) === 'favorable') {
                $newStatus = 'approved';
            } elseif ($opinionCuo && strtolower($opinionCuo) !== 'favorable') {
                $newStatus = 'rejected';
            }
        }
        
        if ($newStatus) {
            $updateData['status'] = $newStatus;
        }
        
        if (!empty($updateData)) {
            $student->update($updateData);
            Log::info('Statut mis à jour', [
                'student_id' => $student->id,
                'nouveau_statut' => $newStatus,
                'cycle' => $cycle,
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
                DB::transaction(function () use ($student) {
                    // Vérifier si le student existe déjà
                    $existingLink = StudentPendingStudent::where('pending_student_id', $student->id)->first();
                    
                    Log::info('Vérification existingLink', ['exists' => !is_null($existingLink)]);
                    
                    if (!$existingLink) {
                        // Extraire le premier contact
                        $contacts = $student->personalInformation->contacts;
                        if (is_string($contacts)) {
                            $contacts = json_decode($contacts, true);
                        }
                        $phoneNumber = is_array($contacts) ? ($contacts['phone'] ?? $contacts[0] ?? null) : null;
                        
                        // Déterminer la cohorte
                        $cohort = $this->determineCohort($student);
                        
                        // Récupérer le role_id étudiant
                        $studentRoleId = DB::table('roles')->where('name', 'etudiant')->value('id');
                        
                        Log::info('Création Student', ['phone' => $phoneNumber, 'cohort' => $cohort, 'role_id' => $studentRoleId]);
                        
                        // Créer le Student
                        $newStudent = Student::create([
                            'student_id_number' => $phoneNumber ?? 'TEMP-' . $student->id,
                            'password' => bcrypt('password123'),
                        ]);
                        
                        Log::info('Student créé', ['student_id' => $newStudent->id]);
                        
                        // Créer StudentPendingStudent
                        $studentPendingStudent = StudentPendingStudent::create([
                            'student_id' => $newStudent->id,
                            'pending_student_id' => $student->id,
                        ]);
                        
                        Log::info('StudentPendingStudent créé', ['id' => $studentPendingStudent->id]);
                        
                        // Créer AcademicPath
                        AcademicPath::create([
                            'student_pending_student_id' => $studentPendingStudent->id,
                            'academic_year_id' => $student->academic_year_id,
                            'study_level' => $student->level,
                            'cohort' => $cohort,
                            'role_id' => $studentRoleId,
                            'year_decision' => null,
                            'financial_status' => $student->exonere === 'Oui' ? 'Exonéré' : 'Non exonéré',
                        ]);
                        
                        Log::info('AcademicPath créé');
                    }
                });
            } catch (\Exception $e) {
                Log::error('Erreur création Student', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }
    }
    
    private function determineCohort($pendingStudent): ?string
    {
        $periods = DB::table('submission_periods')
            ->where('academic_year_id', $pendingStudent->academic_year_id)
            ->select('start_date', 'end_date')
            ->distinct()
            ->orderBy('start_date')
            ->get();
        
        if ($periods->isEmpty()) {
            return '1';
        }
        
        $createdAt = \Carbon\Carbon::parse($pendingStudent->created_at);
        $cohortNumber = 1;
        
        foreach ($periods as $index => $period) {
            $startDate = \Carbon\Carbon::parse($period->start_date);
            $endDate = \Carbon\Carbon::parse($period->end_date);
            
            if ($createdAt->between($startDate, $endDate)) {
                $cohortNumber = $index + 1;
                break;
            }
        }
        
        return (string)$cohortNumber;
    }
}

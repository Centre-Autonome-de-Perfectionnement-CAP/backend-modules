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
        $filiere = strtolower($student->department->name ?? '');
        $newStatus = null;
        
        if (strpos($filiere, 'prepa') !== false) {
            // Pour les filières contenant "prepa", seul CUCA compte
            if ($opinionCuca && strtolower($opinionCuca) === 'favorable') {
                $newStatus = 'approved';
            } elseif ($opinionCuca && strtolower($opinionCuca) !== 'favorable') {
                $newStatus = 'rejected';
            }
        } else {
            // Pour les autres filières, CUO compte
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
                'filiere' => $filiere,
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
        $isFavorable = ($opinionCuca && strtolower($opinionCuca) === 'favorable') || 
                       ($opinionCuo && strtolower($opinionCuo) === 'favorable');
        
        Log::info('Vérification favorable', [
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
                        
                        Log::info('Création Student', ['phone' => $phoneNumber]);
                        
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
}

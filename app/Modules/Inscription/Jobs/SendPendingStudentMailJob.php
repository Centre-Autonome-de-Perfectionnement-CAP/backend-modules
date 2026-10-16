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


class SendPendingStudentMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        // Email + WhatsApp en jobs indépendants. Publiés une seule fois : si
        // la suite (création de l'étudiant) échoue et que ce job est
        // retenté, le candidat ne reçoit pas de doublons.
        if ($this->attempts() <= 1) {
            $notifier = app(\App\Modules\Core\Services\Notifier::class);
            $pi = $student->personalInformation;

            // Envois de masse (décisions CUCA/CUO par lots) : cadencés.
            $notifier->email($pi->email, $subject, "core::emails.{$template}", $mailData, bulk: true);

            $nom = trim($pi->first_names . ' ' . $pi->last_name);
            $filiere = $student->department->name ?? '';
            $commentaire = trim((string) ($this->studentData['commentaireCuo'] ?? $this->studentData['commentaireCuca'] ?? ''));
            $wa = $template === 'acceptation-candidature'
                ? "*CAP-EPAC — Candidature acceptée*\nFélicitations {$nom} !\nVotre candidature en *{$filiere}* a été acceptée. "
                    . "Les informations relatives à votre admission vous ont été transmises par e-mail."
                : "*CAP-EPAC — Décision de candidature*\nBonjour {$nom},\nAprès examen, votre candidature en *{$filiere}* n'a pas été retenue."
                    . ($commentaire !== '' ? "\nObservation : _{$commentaire}_" : '')
                    . "\nLe détail vous a été transmis par e-mail.";

            $notifier->whatsapp(
                $notifier->phoneFromContacts($pi->contacts),
                $wa,
                "inscription-decision:{$student->tracking_code}",
                'Inscription',
                bulk: true,
            );

            Log::info('Décision de candidature mise en file (email + WhatsApp)', [
                'student_id' => $student->id,
                'opinionCuca' => $opinionCuca,
                'opinionCuo' => $opinionCuo,
            ]);
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
                // Vérifier qu'un tarif existe avant de créer l'étudiant
                // COMMENTÉ TEMPORAIREMENT - À réactiver plus tard
                /*
                $feeExists = \App\Modules\Finance\Models\AcademicLevelFee::where('academic_year_id', $student->academic_year_id)
                    ->where('department_id', $student->department_id)
                    ->where('study_level', $student->level)
                    ->where('is_active', true)
                    ->exists();

                if (!$feeExists) {
                    Log::error('Impossible de créer l\'étudiant: aucun tarif défini', [
                        'pending_student_id' => $student->id,
                        'academic_year_id' => $student->academic_year_id,
                        'department_id' => $student->department_id,
                        'level' => $student->level,
                    ]);
                    throw new \Exception('Impossible d\'accepter cet étudiant. Aucun tarif n\'est défini pour le niveau ' . $student->level . ' de la filière ' . $student->department->name . '. Veuillez configurer les tarifs avant d\'accepter des étudiants.');
                }
                */

                // Vérifier si le student existe déjà
                $existingLink = StudentPendingStudent::where('pending_student_id', $student->id)->first();
                
                Log::info('Vérification existingLink', ['exists' => !is_null($existingLink)]);
                
                if (!$existingLink) {
                    // Utiliser le PendingStudentService pour créer l'étudiant officiel
                    Log::info('Appel de PendingStudentService::createOfficialStudent');
                    $pendingStudentService = app(\App\Modules\Inscription\Services\PendingStudentService::class);
                    $pendingStudentService->changeStatus($student, 'approved');
                } elseif ($student->status !== 'approved') {
                    // Étudiant déjà lié à un Student officiel (réinscription) : on met
                    // uniquement le statut à jour, sans recréer de lien ni d'AcademicPath.
                    $student->update(['status' => 'approved']);
                    Log::info('Statut approved appliqué (lien existant)', ['pending_student_id' => $student->id]);
                    app(\App\Modules\Inscription\Services\PendingStudentService::class)
                        ->assignToClassGroup($existingLink->student_id, $student);
                }
            } catch (\Exception $e) {
                Log::error('Erreur création Student', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                throw $e;
            }
        }
    }
    

}

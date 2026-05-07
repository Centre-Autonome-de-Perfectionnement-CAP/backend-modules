<?php

namespace App\Modules\Core\Services;

use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Support\Collection;

class BroadcastService
{
    /**
     * Récupère les étudiants selon les critères spécifiés
     * 
     * @param array $criteria [
     *   'cycle_id' => int (optionnel),
     *   'department_ids' => array (optionnel),
     *   'levels' => array (optionnel),
     *   'cohort' => string (optionnel),
     *   'academic_year_id' => int (optionnel, par défaut année courante)
     * ]
     * @return Collection
     */
    public function getStudents(array $criteria): Collection
    {
        // Récupérer l'année académique
        $academicYearId = $criteria['academic_year_id'] ?? null;
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            if (!$currentAcademicYear) {
                \Log::warning('Aucune année académique courante trouvée');
                return collect([]);
            }
            $academicYearId = $currentAcademicYear->id;
        }

        \Log::info('BroadcastService: Récupération étudiants', [
            'criteria' => $criteria,
            'academic_year_id' => $academicYearId,
        ]);

        // Construire la requête
        $studentsQuery = Student::query()
            ->with(['pendingStudents.personalInformation', 'pendingStudents.department'])
            ->whereHas('academicPaths', function ($pathQuery) use ($criteria, $academicYearId) {
                $pathQuery->where('academic_year_id', $academicYearId);

                // Filtrer par niveau si spécifié
                if (isset($criteria['levels']) && !empty($criteria['levels'])) {
                    $pathQuery->whereIn('study_level', $criteria['levels']);
                }

                // Filtrer par cohorte si spécifié
                if (isset($criteria['cohort']) && $criteria['cohort']) {
                    $pathQuery->where('cohort', $criteria['cohort']);
                }

                // Filtrer par département/cycle
                $pathQuery->whereHas('studentPendingStudent', function ($spsQuery) use ($criteria) {
                    $spsQuery->whereHas('pendingStudent', function ($psQuery) use ($criteria) {
                        $psQuery->where('status', 'approved');

                        // Filtrer par cycle si spécifié
                        if (isset($criteria['cycle_id'])) {
                            $psQuery->whereHas('department', function ($deptQuery) use ($criteria) {
                                $deptQuery->where('cycle_id', $criteria['cycle_id']);
                            });
                        }

                        // Filtrer par département si spécifié
                        if (isset($criteria['department_ids']) && !empty($criteria['department_ids'])) {
                            $psQuery->whereIn('department_id', $criteria['department_ids']);
                        }
                    });
                });
            });

        $students = $studentsQuery->get();

        \Log::info('BroadcastService: Étudiants trouvés', [
            'count' => $students->count(),
        ]);

        return $students;
    }

    /**
     * Envoie un email à une liste d'étudiants
     * 
     * @param Collection $students
     * @param \Illuminate\Mail\Mailable $mailable
     * @return array ['sent' => int, 'failed' => int]
     */
    public function sendEmails(Collection $students, $mailable): array
    {
        $sentCount = 0;
        $failedCount = 0;

        foreach ($students as $student) {
            $personalInfo = $student->personalInformation;
            if ($personalInfo && $personalInfo->email) {
                try {
                    \Mail::to($personalInfo->email)->send($mailable);
                    $sentCount++;
                } catch (\Exception $e) {
                    \Log::error('BroadcastService: Erreur envoi email', [
                        'student_id' => $student->id,
                        'email' => $personalInfo->email,
                        'error' => $e->getMessage()
                    ]);
                    $failedCount++;
                }
            }
        }

        return [
            'sent' => $sentCount,
            'failed' => $failedCount,
        ];
    }
}

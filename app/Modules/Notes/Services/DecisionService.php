<?php

namespace App\Modules\Notes\Services;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Notes\Models\LmdSystemGrade;
use App\Modules\Notes\Models\OldSystemGrade;
use App\Modules\Cours\Models\Program;
use Illuminate\Support\Facades\DB;

class DecisionService
{
    /**
     * Préparer les données pour le PV de fin d'année
     */
    public function preparePVFinAnneeData(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, float $validationAverage = 12): array
    {
        \Log::info('DecisionService: Préparation données PV Fin Année', compact('academicYearId', 'departmentId', 'level', 'cohort'));
        
        $academicYear = AcademicYear::find($academicYearId);
        $department = Department::with('cycle')->find($departmentId);
        
        $programsSem1 = $this->getProgramsForSemester($academicYearId, $departmentId, $level, 1);
        $programsSem2 = $this->getProgramsForSemester($academicYearId, $departmentId, $level, 2);

        $uniqueProgramsSem1 = $this->deduplicatePrograms($programsSem1);
        $uniqueProgramsSem2 = $this->deduplicatePrograms($programsSem2);

        $hasSem1 = $uniqueProgramsSem1->count() > 0;
        $hasSem2 = $uniqueProgramsSem2->count() > 0;

        $etudiants = [];
        if ($hasSem1 || $hasSem2) {
            $etudiants = $this->getStudentsForYear($academicYearId, $departmentId, $level, $cohort, $uniqueProgramsSem1, $uniqueProgramsSem2, $hasSem1, $hasSem2, $validationAverage);
        }
        
        return [
            'annee' => $academicYear ? $academicYear->academic_year : '2024-2025',
            'filiere' => $department ? $department->name : 'N/A',
            'classe' => (object)[
                'filiere' => (object)[
                    'nom' => $department ? $department->name : 'N/A',
                    'diplome' => (object)[
                        'lmd' => true,
                        'sigle' => $department && $department->cycle ? $department->cycle->name : 'N/A'
                    ]
                ],
                'niveau' => $level ?? 'N/A',
                'moy_min' => $validationAverage
            ],
            'etudiants' => collect($etudiants),
            'programsSem1' => $uniqueProgramsSem1,
            'programsSem2' => $uniqueProgramsSem2,
            'hasSem1' => $hasSem1,
            'hasSem2' => $hasSem2
        ];
    }

    private function deduplicatePrograms($programs)
    {
        $uniquePrograms = [];
        foreach ($programs as $p) {
            $courseId = $p->courseElementProfessor->courseElement->id ?? null;
            if ($courseId) {
                $weighting = is_array($p->weighting) ? $p->weighting : [];
                $hasWeighting = count($weighting) > 0;
                
                if (!isset($uniquePrograms[$courseId]) || (!$uniquePrograms[$courseId]->hasWeighting && $hasWeighting)) {
                    $uniquePrograms[$courseId] = (object)[
                        'id' => $p->id,
                        'code' => $p->courseElementProfessor->courseElement->code ?? 'N/A',
                        'weighting' => $weighting,
                        'hasWeighting' => $hasWeighting,
                        'matiere_professeur' => (object)[
                            'matiere' => (object)[
                                'libelle' => $p->courseElementProfessor->courseElement->name ?? 'N/A',
                                'code' => $p->courseElementProfessor->courseElement->code ?? 'N/A'
                            ]
                        ]
                    ];
                }
            }
        }
        return collect(array_values($uniquePrograms));
    }

    /**
     * Retourne toutes les variantes textuelles et numériques équivalentes pour un niveau donné
     * Ex: '1' ou 'L1' -> ['1', 'L1', 'M1', 'D1', '1ère Année', '1ere Annee', 'Niveau 1', 'Licence 1', ...]
     */
    public static function getEquivalentLevels(?string $level): array
    {
        if (empty($level) || $level === 'all') {
            return [];
        }

        $levels = [(string)$level];
        $trimmed = trim((string)$level);

        if (preg_match('/(\d+)/', $trimmed, $matches)) {
            $num = $matches[1];
            $variants = [
                $num,
                'L' . $num,
                'M' . $num,
                'D' . $num,
                $num . 'ère Année',
                $num . 'ere Annee',
                $num . 'ère annee',
                $num . 'ere annee',
                $num . 'ème Année',
                $num . 'eme Annee',
                $num . 'ème annee',
                $num . 'eme annee',
                'Niveau ' . $num,
                'niveau ' . $num,
                'Licence ' . $num,
                'licence ' . $num,
                'Master ' . $num,
                'master ' . $num,
                'Doctorat ' . $num,
                'doctorat ' . $num,
                'Année ' . $num,
                'annee ' . $num,
            ];
            $levels = array_unique(array_merge($levels, $variants));
        }

        return array_values($levels);
    }

    /**
     * Récupère les IDs de ClassGroup pour un département et un niveau
     * Note: Le filtre par année a été retiré dans le service de délibération
     */
    public function getClassGroupIds(?int $academicYearId, int $departmentId, ?string $level = null): array
    {
        $classGroupQuery = \App\Modules\Inscription\Models\ClassGroup::where('department_id', $departmentId);

        if (!empty($level) && $level !== 'all') {
            $equivalentLevels = self::getEquivalentLevels($level);
            $ids = (clone $classGroupQuery)->whereIn('study_level', $equivalentLevels)->pluck('id')->toArray();

            if (!empty($ids)) {
                return $ids;
            }
        }

        return $classGroupQuery->pluck('id')->toArray();
    }

    /**
     * Récupère les programmes pour une sélection donnée (filière, niveau, semestre)
     * Note: Le filtre par année a été retiré dans le service de délibération
     */
    public function getProgramsForSemester(?int $academicYearId, int $departmentId, ?string $level = null, int $semester = 1)
    {
        $equivalentLevels = self::getEquivalentLevels($level);
        $classGroupIds = $this->getClassGroupIds($academicYearId, $departmentId, $level);

        $baseQuery = Program::where('semester', $semester)
            ->with(['courseElementProfessor.courseElement.teachingUnit', 'courseElementProfessor.professor', 'classGroup']);

        if (!empty($classGroupIds)) {
            $programs = (clone $baseQuery)->whereIn('class_group_id', $classGroupIds)->get();
            if ($programs->isNotEmpty()) {
                return $programs;
            }
        }

        // Fallback: via classGroup matching department and level
        $programs = (clone $baseQuery)->whereHas('classGroup', function ($cg) use ($departmentId, $equivalentLevels) {
            $cg->where('department_id', $departmentId);
            if (!empty($equivalentLevels)) {
                $cg->whereIn('study_level', $equivalentLevels);
            }
        })->get();

        if ($programs->isNotEmpty()) {
            return $programs;
        }

        // Ultimate fallback: all programs of department and semester
        return (clone $baseQuery)->whereHas('classGroup', function ($cg) use ($departmentId) {
            $cg->where('department_id', $departmentId);
        })->get();
    }

    /**
     * Récupère la note d'un étudiant pour un programme, en cherchant dans LmdSystemGrade et OldSystemGrade
     */
    public function getStudentGradeData(array $spsIds, $program): ?array
    {
        if (empty($spsIds)) {
            return null;
        }

        $programId = is_object($program) ? $program->id : (int)$program;
        $courseElementId = is_object($program) ? ($program->courseElementProfessor?->course_element_id ?? null) : null;

        $siblingProgramIds = [$programId];
        if ($courseElementId) {
            $siblings = Program::whereHas('courseElementProfessor', function ($cep) use ($courseElementId) {
                $cep->where('course_element_id', $courseElementId);
            })->pluck('id')->toArray();
            $siblingProgramIds = array_unique(array_merge($siblingProgramIds, $siblings));
        }

        // 1. Chercher dans LmdSystemGrade
        $lmdGrade = LmdSystemGrade::whereIn('student_pending_student_id', $spsIds)
            ->whereIn('program_id', $siblingProgramIds)
            ->orderByRaw('CASE WHEN program_id = ' . (int)$programId . ' THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN retake_average IS NOT NULL THEN retake_average ELSE average END DESC')
            ->first();

        if ($lmdGrade && ($lmdGrade->average !== null || $lmdGrade->retake_average !== null || !empty($lmdGrade->grades) || !empty($lmdGrade->retake_grades))) {
            $finalAvg = $lmdGrade->retake_average ?? $lmdGrade->average ?? 0;
            $rawGrades = (is_array($lmdGrade->retake_grades) && !empty($lmdGrade->retake_grades))
                ? $lmdGrade->retake_grades
                : (is_array($lmdGrade->grades) ? $lmdGrade->grades : []);
            
            $isValidated = (bool)($lmdGrade->validated || $finalAvg >= 10);

            return [
                'grades' => $rawGrades,
                'average' => (float)$finalAvg,
                'retake_average' => $lmdGrade->retake_average,
                'validated' => $isValidated,
                'is_retake' => $lmdGrade->retake_average !== null,
                'system' => 'lmd',
            ];
        }

        // 2. Chercher dans OldSystemGrade
        $oldGrade = OldSystemGrade::whereIn('student_pending_student_id', $spsIds)
            ->whereIn('program_id', $siblingProgramIds)
            ->orderByRaw('CASE WHEN program_id = ' . (int)$programId . ' THEN 0 ELSE 1 END')
            ->orderBy('average', 'desc')
            ->first();

        if ($oldGrade && ($oldGrade->average !== null || !empty($oldGrade->grades))) {
            $finalAvg = (float)($oldGrade->average ?? 0);
            $rawGrades = is_array($oldGrade->grades) ? $oldGrade->grades : [];
            $isValidated = $finalAvg >= 10;

            return [
                'grades' => $rawGrades,
                'average' => $finalAvg,
                'retake_average' => null,
                'validated' => $isValidated,
                'is_retake' => false,
                'system' => 'old',
            ];
        }

        return null;
    }

    /**
     * Récupère la collection de parcours académiques (avec fallback automatique sur les étudiants inscrits)
     * Note: Le filtre par année a été retiré dans le service de délibération
     */
    public function getAcademicPaths(?int $academicYearId, int $departmentId, ?string $level = null, ?string $cohort = null)
    {
        $equivalentLevels = self::getEquivalentLevels($level);

        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.student'
        ])
        ->where(function ($q) use ($departmentId) {
            $q->whereHas('studentPendingStudent.pendingStudent', function ($psQuery) use ($departmentId) {
                $psQuery->where('department_id', $departmentId);
            })
            ->orWhereHas('studentPendingStudent.student.studentGroups.classGroup', function ($cgQuery) use ($departmentId) {
                $cgQuery->where('department_id', $departmentId);
            });
        });

        if (!empty($equivalentLevels)) {
            $query->where(function ($q) use ($equivalentLevels) {
                $q->whereIn('study_level', $equivalentLevels)
                  ->orWhere(function ($sub) use ($equivalentLevels) {
                      $sub->where(function ($emptyCheck) {
                          $emptyCheck->whereNull('study_level')->orWhere('study_level', '');
                      })
                      ->whereHas('studentPendingStudent.pendingStudent', function ($ps) use ($equivalentLevels) {
                          $ps->whereIn('level', $equivalentLevels);
                      });
                  });
            });
        }

        if (!empty($cohort) && $cohort !== 'all') {
            $cohortStr = (string)$cohort;
            $cohortNum = preg_replace('/[^0-9]/', '', $cohortStr);
            $query->where(function ($q) use ($cohortStr, $cohortNum) {
                $q->where('cohort', $cohortStr)
                  ->orWhere('cohort', 'Cohorte ' . $cohortStr)
                  ->orWhere('cohort', 'Vague ' . $cohortStr);
                if (!empty($cohortNum)) {
                    $q->orWhere('cohort', $cohortNum)
                      ->orWhere('cohort', 'Cohorte ' . $cohortNum)
                      ->orWhere('cohort', 'Vague ' . $cohortNum)
                      ->orWhereHas('studentPendingStudent.pendingStudent', function ($ps) use ($cohortNum) {
                          $ps->where('initial_wave', (int)$cohortNum);
                      });
                }
            });
        }

        $query->where(function ($q) {
            $q->where('year_decision', '!=', 'failed')
              ->orWhereNull('year_decision');
        });

        // Dédupliquer les AcademicPaths par étudiant (le plus récent en premier)
        $academicPaths = $query->orderBy('id', 'desc')->get()->unique(function ($ap) {
            return $ap->studentPendingStudent?->student_id ?? $ap->student_pending_student_id;
        })->values();

        // Récupérer également les StudentPendingStudent non encore enregistrés dans academic_paths
        $existingStudentIds = $academicPaths->map(function ($ap) {
            return $ap->studentPendingStudent?->student_id;
        })->filter()->toArray();

        $existingSpsIds = $academicPaths->pluck('student_pending_student_id')->filter()->toArray();

        $spsQuery = \App\Modules\Inscription\Models\StudentPendingStudent::with([
            'pendingStudent.personalInformation',
            'student'
        ])
        ->whereHas('pendingStudent', function ($q) use ($departmentId, $equivalentLevels, $cohort) {
            $q->where('department_id', $departmentId);
            if (!empty($equivalentLevels)) {
                $q->whereIn('level', $equivalentLevels);
            }
            if (!empty($cohort) && $cohort !== 'all') {
                $cohortNum = (int) preg_replace('/[^0-9]/', '', (string)$cohort);
                if ($cohortNum > 0) {
                    $q->where('initial_wave', $cohortNum);
                }
            }
        });

        if (!empty($existingSpsIds)) {
            $spsQuery->whereNotIn('id', $existingSpsIds);
        }
        if (!empty($existingStudentIds)) {
            $spsQuery->whereNotIn('student_id', $existingStudentIds);
        }

        $extraSps = $spsQuery->get()->unique(function ($sps) {
            return $sps->student_id ?? $sps->id;
        });

        foreach ($extraSps as $sps) {
            $apLevel = $level ?: ($sps->pendingStudent?->level ?? '1');
            $defaultYearId = $academicYearId ?: ($sps->pendingStudent?->academic_year_id ?? 1);
            $ap = AcademicPath::firstOrCreate([
                'student_pending_student_id' => $sps->id,
                'academic_year_id' => $defaultYearId,
            ], [
                'study_level' => $apLevel,
                'cohort' => $cohort ?: ($sps->pendingStudent?->initial_wave ? (string)$sps->pendingStudent->initial_wave : '1'),
                'financial_status' => $sps->pendingStudent?->exonere ? 'Exonéré' : 'Non exonéré',
            ]);
            $ap->setRelation('studentPendingStudent', $sps);
            $academicPaths->push($ap);
        }

        return $academicPaths;
    }

    private function getStudentsForYear($academicYearId, $departmentId, $level, $cohort, $programsSem1, $programsSem2, $hasSem1, $hasSem2, $validationAverage)
    {
        $academicPaths = $this->getAcademicPaths($academicYearId, $departmentId, $level, $cohort);

        return $academicPaths->map(function ($academicPath) use ($programsSem1, $programsSem2, $hasSem1, $hasSem2) {
            $studentPending = $academicPath->studentPendingStudent;
            $pendingStudent = $studentPending?->pendingStudent;
            $personalInfo = $pendingStudent?->personalInformation;
            $student = $studentPending?->student;

            $spsIds = array_filter([$academicPath->student_pending_student_id]);
            if ($student?->id) {
                $extra = \App\Modules\Inscription\Models\StudentPendingStudent::where('student_id', $student->id)->pluck('id')->toArray();
                $spsIds = array_unique(array_merge($spsIds, $extra));
            }

            $moyennesSem1 = [];
            $moyenneSem1 = 0;
            $countSem1 = 0;
            $hasZeroSem1 = false;
            if ($hasSem1) {
                foreach ($programsSem1 as $program) {
                    $gradeData = $this->getStudentGradeData($spsIds, $program);
                    $avg = $gradeData ? $gradeData['average'] : 0;
                    $moyennesSem1[] = $avg > 0 ? $avg : '-';
                    if ($avg > 0) {
                        $moyenneSem1 += $avg;
                        $countSem1++;
                    }
                    if ($gradeData && $avg === 0.0 && count($gradeData['grades'] ?? []) > 0) {
                        $hasZeroSem1 = true;
                    }
                }
                $moyenneSem1 = $countSem1 > 0 ? round($moyenneSem1 / $countSem1, 2) : 0;
            }

            $moyennesSem2 = [];
            $moyenneSem2 = 0;
            $countSem2 = 0;
            $hasZeroSem2 = false;
            if ($hasSem2) {
                foreach ($programsSem2 as $program) {
                    $gradeData = $this->getStudentGradeData($spsIds, $program);
                    $avg = $gradeData ? $gradeData['average'] : 0;
                    $moyennesSem2[] = $avg > 0 ? $avg : '-';
                    if ($avg > 0) {
                        $moyenneSem2 += $avg;
                        $countSem2++;
                    }
                    if ($gradeData && $avg === 0.0 && count($gradeData['grades'] ?? []) > 0) {
                        $hasZeroSem2 = true;
                    }
                }
                $moyenneSem2 = $countSem2 > 0 ? round($moyenneSem2 / $countSem2, 2) : 0;
            }

            $moyenneAnnuelle = 0;
            $countTotal = 0;
            if ($moyenneSem1 > 0) { $moyenneAnnuelle += $moyenneSem1; $countTotal++; }
            if ($moyenneSem2 > 0) { $moyenneAnnuelle += $moyenneSem2; $countTotal++; }
            $moyenneAnnuelle = $countTotal > 0 ? round($moyenneAnnuelle / $countTotal, 2) : 0;

            return (object)[
                'id' => $student?->id ?? $academicPath->id,
                'matricule' => $student?->student_id_number ?? 'N/A',
                'nom' => $personalInfo?->last_name ?? 'N/A',
                'prenoms' => $personalInfo?->first_names ?? 'N/A',
                'isRedoublant' => $academicPath->is_repeating ?? false,
                'moyennesSem1' => $moyennesSem1,
                'moyenneSem1' => $moyenneSem1,
                'moyennesSem2' => $moyennesSem2,
                'moyenneSem2' => $moyenneSem2,
                'moyenneAnnuelle' => $moyenneAnnuelle,
                'hasZero' => $hasZeroSem1 || $hasZeroSem2
            ];
        })->filter(function ($item) {
            return $item->nom !== 'N/A' && $item->prenoms !== 'N/A';
        })->sortBy('nom')->values()->toArray();
    }

    /**
     * Préparer les données pour le PV de délibération
     */
    public function preparePVDeliberationData(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, int $semester): array
    {
        \Log::info('DecisionService: Préparation données PV Délibération', compact('academicYearId', 'departmentId', 'level', 'cohort', 'semester'));
        
        $academicYear = AcademicYear::find($academicYearId);
        $department = Department::find($departmentId);
        
        $etudiants = $this->getStudentsBySemester($academicYearId, $departmentId, $level, $cohort, $semester);
        
        return [
            'annee' => $academicYear->libelle ?? '2024-2025',
            'filiere' => $department->name ?? 'N/A',
            'classe' => (object)[
                'filiere' => (object)[
                    'nom' => $department->name ?? 'N/A',
                    'diplome' => (object)[
                        'lmd' => true,
                        'sigle' => 'LMD'
                    ]
                ],
                'niveau' => $level ?? 'N/A',
                'moy_min' => 2.4,
                'cred_sem1' => 30,
                'cred_sem2' => 30
            ],
            'sem' => $semester,
            'etudiants' => collect($etudiants),
            'nt' => [],
            'moyennes' => [],
            'credits' => [],
            'programmes' => [],
            'nd' => count($etudiants),
            'etudiants_reprise' => collect([]),
            'ntr' => [],
            'moyennesr' => [],
            'creditsr' => []
        ];
    }

    /**
     * Préparer les données pour le récap des notes
     */
    public function prepareRecapNotesData(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, int $semester): array
    {
        \Log::info('DecisionService: Préparation données Récap Notes', compact('academicYearId', 'departmentId', 'level', 'cohort', 'semester'));
        
        $academicYear = AcademicYear::find($academicYearId);
        $department = Department::with('cycle')->find($departmentId);
        
        $etudiants = $this->getStudentsBySemesterOldSystem($academicYearId, $departmentId, $level, $cohort, $semester);
        
        $classGroupIds = $this->getClassGroupIds($academicYearId, $departmentId, $level);
        
        $programsQuery = Program::whereIn('class_group_id', $classGroupIds)
            ->where('semester', $semester)
            ->with('courseElementProfessor.courseElement')
            ->get();

        $programsByCourse = [];
        foreach ($programsQuery as $p) {
            $courseId = $p->courseElementProfessor->courseElement->id ?? null;
            if ($courseId) {
                if (!isset($programsByCourse[$courseId])) {
                    $programsByCourse[$courseId] = [];
                }
                $programsByCourse[$courseId][] = $p;
            }
        }
        
        $uniquePrograms = [];
        $programsById = [];
        foreach ($programsByCourse as $courseId => $coursePrograms) {
            $maxWeightCount = 0;
            $selectedProgram = $coursePrograms[0];
            
            foreach ($coursePrograms as $p) {
                $weighting = is_array($p->weighting) ? $p->weighting : [];
                $weightCount = count($weighting);
                if ($weightCount > $maxWeightCount) {
                    $maxWeightCount = $weightCount;
                    $selectedProgram = $p;
                }
            }
            
            if ($selectedProgram) {
                $maxWeightCount = max($maxWeightCount, 0);
                $weighting = is_array($selectedProgram->weighting) ? $selectedProgram->weighting : [];
                $uniquePrograms[$courseId] = (object)[
                    'id' => $selectedProgram->id,
                    'code' => $selectedProgram->courseElementProfessor->courseElement->code ?? 'N/A',
                    'weighting' => $weighting,
                    'maxWeightCount' => $maxWeightCount,
                    'hasWeighting' => $maxWeightCount > 0,
                    'allProgramIds' => array_map(fn($p) => $p->id, $coursePrograms),
                    'matiere_professeur' => (object)[
                        'matiere' => (object)[
                            'libelle' => $selectedProgram->courseElementProfessor->courseElement->name ?? 'N/A',
                            'code' => $selectedProgram->courseElementProfessor->courseElement->code ?? 'N/A'
                        ]
                    ]
                ];
                foreach ($coursePrograms as $p) {
                    $programsById[$p->id] = $uniquePrograms[$courseId];
                }
            }
        }
        $programs = collect(array_values($uniquePrograms));
        
        \Log::info('Programs with weighting', ['programs' => $programs->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'weighting' => $p->weighting, 'maxWeightCount' => $p->maxWeightCount, 'allProgramIds' => $p->allProgramIds])->toArray()]);

        $nt = [];
        $moyennes = [];
        foreach ($etudiants as $i => $etudiant) {
            $nt[$i] = [];
            $moyennes[$i] = [];
            \Log::info('Processing student', ['index' => $i, 'nom' => $etudiant->nom, 'gradeDetailsKeys' => array_keys($etudiant->gradeDetails)]);
            
            foreach ($programs as $program) {
                $gradeData = null;
                \Log::info('Processing program', ['code' => $program->code, 'allProgramIds' => $program->allProgramIds, 'maxWeightCount' => $program->maxWeightCount]);
                
                foreach ($program->allProgramIds as $progId) {
                    if (isset($etudiant->gradeDetails[$progId])) {
                        $tempGradeData = $etudiant->gradeDetails[$progId];
                        if (isset($tempGradeData['grades']) && is_array($tempGradeData['grades']) && count($tempGradeData['grades']) > 0) {
                            $gradeData = $tempGradeData;
                            \Log::info('Found grade data with notes', ['progId' => $progId, 'grades' => $gradeData['grades'], 'average' => $gradeData['average'] ?? null]);
                            break;
                        } elseif (!$gradeData) {
                            $gradeData = $tempGradeData;
                            \Log::info('Found grade data without notes', ['progId' => $progId]);
                        }
                    }
                }
                
                $maxWeightCount = $program->maxWeightCount;
                
                if ($gradeData && isset($gradeData['grades']) && is_array($gradeData['grades']) && count($gradeData['grades']) > 0) {
                    $studentGrades = $gradeData['grades'];
                    \Log::info('Adding student grades', ['grades' => $studentGrades, 'count' => count($studentGrades)]);
                    foreach ($studentGrades as $grade) {
                        $nt[$i][] = $grade === -1 ? 'ABS' : $grade;
                    }
                    for ($j = count($studentGrades); $j < $maxWeightCount; $j++) {
                        $nt[$i][] = '-';
                    }
                    $moyennes[$i][] = $gradeData['average'] ?? 0;
                } else {
                    \Log::info('No grade data, adding dashes', ['maxWeightCount' => $maxWeightCount]);
                    for ($j = 0; $j < $maxWeightCount; $j++) {
                        $nt[$i][] = '-';
                    }
                    $moyennes[$i][] = $maxWeightCount > 0 ? '-' : 'N/A';
                }
            }
            \Log::info('Final nt for student', ['index' => $i, 'nt' => $nt[$i], 'moyennes' => $moyennes[$i]]);
        }
        
        return [
            'annee' => $academicYear ? $academicYear->academic_year : '2024-2025',
            'filiere' => $department ? $department->name : 'N/A',
            'classe' => (object)[
                'filiere' => (object)[
                    'nom' => $department ? $department->name : 'N/A',
                    'diplome' => (object)[
                        'lmd' => true,
                        'sigle' => $department && $department->cycle ? $department->cycle->name : 'N/A'
                    ]
                ],
                'niveau' => $level ?? 'N/A',
                'moy_min' => 12
            ],
            'sem' => $semester,
            'etudiants' => collect($etudiants),
            'nt' => $nt,
            'moyennes' => $moyennes,
            'programmes' => $programs,
            'nd' => $programs->sum(function($p) { return (is_array($p->weighting) ? count($p->weighting) : 0) + 1; }),
            'ncol' => 0,
            'etudiants_rattrape' => collect([]),
            'ntre' => [],
            'etudiants_reprise' => collect([]),
            'color' => [],
            'colore' => []
        ];
    }

    public function getStudentsBySemester(int $academicYearId, int $departmentId, ?string $level, ?string $cohort = null, int $semester = 1): array
    {
        \Log::info('DecisionService: Récupération étudiants semestre', compact('academicYearId', 'departmentId', 'level', 'cohort', 'semester'));
        
        $programsData = $this->getProgramsForSemester($academicYearId, $departmentId, $level, $semester);
        $academicPaths = $this->getAcademicPaths($academicYearId, $departmentId, $level, $cohort);

        // Pré-calculer les crédits totaux du semestre
        $totalSemesterCredits = 0;
        if ($programsData->isNotEmpty()) {
            foreach ($programsData as $p) {
                $c = $p->courseElementProfessor?->courseElement?->credits ?? 3;
                $totalSemesterCredits += ($c > 0 ? $c : 3);
            }
        }
        if ($totalSemesterCredits <= 0) {
            $totalSemesterCredits = 30;
        }

        return $academicPaths->map(function ($academicPath) use ($programsData, $semester, $level, $totalSemesterCredits) {
            $studentPending = $academicPath->studentPendingStudent;
            $pendingStudent = $studentPending?->pendingStudent;
            $personalInfo = $pendingStudent?->personalInformation;
            $student = $studentPending?->student;

            $nom = $personalInfo?->last_name ?? '';
            $prenoms = $personalInfo?->first_names ?? '';
            $matricule = $student?->student_id_number ?? 'N/A';

            if (empty($nom) && empty($prenoms)) {
                $nom = 'Étudiant';
                $prenoms = $matricule;
            }

            // Collecter tous les SPS IDs pour cet étudiant
            $spsIds = array_filter([$academicPath->student_pending_student_id]);
            if ($student?->id) {
                $extraSps = \App\Modules\Inscription\Models\StudentPendingStudent::where('student_id', $student->id)->pluck('id')->toArray();
                $spsIds = array_unique(array_merge($spsIds, $extraSps));
            }

            $gradeDetails = [];
            $earnedCredits = 0;
            $totalWeight = 0;
            $weightedSum = 0;
            $hasAnyGrade = false;

            if ($programsData->isNotEmpty()) {
                foreach ($programsData as $program) {
                    $courseCredits = $program->courseElementProfessor?->courseElement?->credits ?? 3;
                    if ($courseCredits <= 0) {
                        $courseCredits = 3;
                    }

                    $gradeData = $this->getStudentGradeData($spsIds, $program);

                    if ($gradeData) {
                        $hasAnyGrade = true;
                        $gradeDetails[$program->id] = [
                            'grades' => $gradeData['grades'],
                            'average' => $gradeData['average'],
                            'retake_average' => $gradeData['retake_average'],
                            'validated' => $gradeData['validated'],
                        ];
                        $weightedSum += ($gradeData['average'] * $courseCredits);
                        $totalWeight += $courseCredits;

                        if ($gradeData['validated']) {
                            $earnedCredits += $courseCredits;
                        }
                    } else {
                        $gradeDetails[$program->id] = [
                            'grades' => [],
                            'average' => 0,
                            'retake_average' => null,
                            'validated' => false,
                        ];
                        $totalWeight += $courseCredits;
                    }
                }
            }

            $moyenneGenerale = ($totalWeight > 0 && $hasAnyGrade) ? round($weightedSum / $totalWeight, 2) : 0;

            $currentLevel = $academicPath->study_level ?: ($pendingStudent?->level ?: ($level ?: 'L1'));

            return [
                'id' => $student?->id ?? $academicPath->id,
                'student_id' => $student?->id ?? $academicPath->id,
                'student_pending_student_id' => $academicPath->student_pending_student_id,
                'matricule' => $matricule,
                'nom' => $nom,
                'prenoms' => $prenoms,
                'prenom' => $prenoms,
                'level' => $currentLevel,
                'study_level' => $currentLevel,
                'moyenne' => $moyenneGenerale,
                'credits' => $earnedCredits,
                'totalCredits' => $totalSemesterCredits,
                'gradeDetails' => $gradeDetails,
                'hasNotes' => $hasAnyGrade,
            ];
        })->sortBy('nom')->values()->toArray();
    }

    public function getStudentsByYear(int $academicYearId, int $departmentId, ?string $level, ?string $cohort = null): array
    {
        \Log::info('DecisionService: Récupération étudiants année', compact('academicYearId', 'departmentId', 'level', 'cohort'));
        
        $sem1Students = $this->getStudentsBySemester($academicYearId, $departmentId, $level, $cohort, 1);
        $sem2Students = $this->getStudentsBySemester($academicYearId, $departmentId, $level, $cohort, 2);

        $studentsById = [];
        
        foreach ($sem1Students as $item) {
            $student = (array) $item;
            $id = $student['id'];
            $studentsById[$id] = [
                'id' => $id,
                'student_id' => $id,
                'student_pending_student_id' => $student['student_pending_student_id'] ?? null,
                'matricule' => $student['matricule'] ?? 'N/A',
                'nom' => $student['nom'],
                'prenoms' => $student['prenoms'] ?? $student['prenom'] ?? '',
                'prenom' => $student['prenoms'] ?? $student['prenom'] ?? '',
                'level' => $student['level'] ?? $level ?? 'L1',
                'study_level' => $student['level'] ?? $level ?? 'L1',
                'moyenne_s1' => (float)($student['moyenne'] ?? 0),
                'moyenneS1' => (float)($student['moyenne'] ?? 0),
                'credits_s1' => (int)($student['credits'] ?? 0),
                'creditsS1' => (int)($student['credits'] ?? 0),
                'moyenne_s2' => 0.0,
                'moyenneS2' => 0.0,
                'credits_s2' => 0,
                'creditsS2' => 0,
                'moyenne_annuelle' => 0.0,
                'moyenneAnnuelle' => 0.0,
                'credits_total' => (int)($student['credits'] ?? 0),
                'creditsTotal' => (int)($student['credits'] ?? 0),
                'totalCredits' => ($student['totalCredits'] ?? 30) * 2,
                'hasNotesS1' => $student['hasNotes'] ?? false,
                'hasNotesS2' => false,
            ];
        }

        foreach ($sem2Students as $item) {
            $student = (array) $item;
            $id = $student['id'];
            if (isset($studentsById[$id])) {
                $studentsById[$id]['moyenne_s2'] = (float)($student['moyenne'] ?? 0);
                $studentsById[$id]['moyenneS2'] = (float)($student['moyenne'] ?? 0);
                $studentsById[$id]['credits_s2'] = (int)($student['credits'] ?? 0);
                $studentsById[$id]['creditsS2'] = (int)($student['credits'] ?? 0);
                $studentsById[$id]['hasNotesS2'] = $student['hasNotes'] ?? false;
            } else {
                $studentsById[$id] = [
                    'id' => $id,
                    'student_id' => $id,
                    'student_pending_student_id' => $student['student_pending_student_id'] ?? null,
                    'matricule' => $student['matricule'] ?? 'N/A',
                    'nom' => $student['nom'],
                    'prenoms' => $student['prenoms'] ?? $student['prenom'] ?? '',
                    'prenom' => $student['prenoms'] ?? $student['prenom'] ?? '',
                    'level' => $student['level'] ?? $level ?? 'L1',
                    'study_level' => $student['level'] ?? $level ?? 'L1',
                    'moyenne_s1' => 0.0,
                    'moyenneS1' => 0.0,
                    'credits_s1' => 0,
                    'creditsS1' => 0,
                    'moyenne_s2' => (float)($student['moyenne'] ?? 0),
                    'moyenneS2' => (float)($student['moyenne'] ?? 0),
                    'credits_s2' => (int)($student['credits'] ?? 0),
                    'creditsS2' => (int)($student['credits'] ?? 0),
                    'moyenne_annuelle' => 0.0,
                    'moyenneAnnuelle' => 0.0,
                    'credits_total' => (int)($student['credits'] ?? 0),
                    'creditsTotal' => (int)($student['credits'] ?? 0),
                    'totalCredits' => ($student['totalCredits'] ?? 30) * 2,
                    'hasNotesS1' => false,
                    'hasNotesS2' => $student['hasNotes'] ?? false,
                ];
            }
        }

        foreach ($studentsById as &$student) {
            $s1HasNotes = $student['hasNotesS1'];
            $s2HasNotes = $student['hasNotesS2'];
            
            if ($s1HasNotes && $s2HasNotes) {
                $moyAnnuelle = round(($student['moyenne_s1'] + $student['moyenne_s2']) / 2, 2);
            } elseif ($s1HasNotes) {
                $moyAnnuelle = $student['moyenne_s1'];
            } elseif ($s2HasNotes) {
                $moyAnnuelle = $student['moyenne_s2'];
            } else {
                $moyAnnuelle = 0.0;
            }

            $creditsTotal = $student['credits_s1'] + $student['credits_s2'];
            $student['moyenne_annuelle'] = $moyAnnuelle;
            $student['moyenneAnnuelle'] = $moyAnnuelle;
            $student['credits_total'] = $creditsTotal;
            $student['creditsTotal'] = $creditsTotal;
        }

        return array_values($studentsById);
    }

    public function getStudentsBySemesterOldSystem(int $academicYearId, int $departmentId, ?string $level, ?string $cohort = null, int $semester = 1): array
    {
        return $this->getStudentsBySemester($academicYearId, $departmentId, $level, $cohort, $semester);
    }

    public function saveSemesterDecisions(array $decisions): int
    {
        $count = 0;
        foreach ($decisions as $decision) {
            $spsId = $decision['student_pending_student_id'] ?? null;
            if (!$spsId && !empty($decision['student_id'])) {
                $spsId = \App\Modules\Inscription\Models\StudentPendingStudent::where('student_id', $decision['student_id'])->value('id');
            }
            if ($spsId) {
                $academicPath = AcademicPath::where('student_pending_student_id', $spsId)->first();
                if ($academicPath) {
                    $academicPath->semester_decision = $decision['semester_decision'] ?? $decision['decision'] ?? null;
                    $academicPath->save();
                    $count++;
                }
            }
        }
        return $count;
    }

    public function saveYearDecisions(array $decisions, ?string $deliberationDate = null): int
    {
        $count = 0;
        $dateToUse = $deliberationDate ? \Carbon\Carbon::parse($deliberationDate) : now();
        
        foreach ($decisions as $decision) {
            $spsId = $decision['student_pending_student_id'] ?? null;
            if (!$spsId && !empty($decision['student_id'])) {
                $spsId = \App\Modules\Inscription\Models\StudentPendingStudent::where('student_id', $decision['student_id'])->value('id');
            }
            if ($spsId) {
                $academicPath = AcademicPath::where('student_pending_student_id', $spsId)->first();
                if ($academicPath) {
                    $academicPath->year_decision = $decision['year_decision'] ?? $decision['decision'] ?? null;
                    $academicPath->deliberation_date = $dateToUse;
                    $academicPath->save();
                    $count++;
                }
            }
        }
        return $count;
    }

    public function processYearDeliberationAndProgression(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, float $validationAverage = 12, ?string $deliberationDate = null, array $studentData = []): array
    {
        \Log::info('=== DEBUT processYearDeliberationAndProgression ===', compact('academicYearId', 'departmentId', 'level', 'cohort', 'validationAverage'));
        
        $dateToUse = $deliberationDate ? \Carbon\Carbon::parse($deliberationDate) : now();
        \Log::info('Date de délibération', ['date' => $dateToUse->format('Y-m-d')]);

        // Mettre à jour la moyenne minimale dans les ClassGroups concernés
        \App\Modules\Inscription\Models\ClassGroup::where('department_id', $departmentId)
            ->where('study_level', $level)
            ->update(['validation_average' => $validationAverage]);

        $department = Department::find($departmentId);
        $isPrepa = $department && stripos($department->name, 'prepa') !== false;
        \Log::info('Type de filière', ['department_name' => $department?->name, 'is_prepa' => $isPrepa]);

        // Rechercher l'année académique suivante
        $nextYear = \App\Modules\Inscription\Models\AcademicYear::where('year_start', '>', 
            \App\Modules\Inscription\Models\AcademicYear::find($academicYearId)->year_start)
            ->orderBy('year_start', 'asc')
            ->first();
        \Log::info('Année académique suivante', ['next_year_id' => $nextYear?->id, 'next_year_label' => $nextYear?->academic_year]);

        $academicPaths = $this->getAcademicPaths($academicYearId, $departmentId, $level, $cohort);
        \Log::info('Nombre de parcours académiques trouvés', ['count' => $academicPaths->count()]);
        \Log::info('Données étudiants reçues', ['count' => count($studentData)]);
        
        $updated = 0;
        $progressed = 0;

        foreach ($academicPaths as $index => $academicPath) {
            $studentId = $academicPath->studentPendingStudent?->student?->id;
            \Log::info("Traitement étudiant $index", ['student_id' => $studentId, 'academic_path_id' => $academicPath->id]);
            
            $studentInfo = collect($studentData)->firstWhere('id', $studentId);
            
            if (!$studentInfo) {
                \Log::warning('Étudiant non trouvé dans les données', ['student_id' => $studentId]);
                continue;
            }

            $moyenne = $studentInfo->moyenneAnnuelle ?? 0;
            $hasZero = $studentInfo->hasZero ?? false;
            \Log::info('Moyenne et zéro', ['moyenne' => $moyenne, 'hasZero' => $hasZero, 'validationAverage' => $validationAverage]);

            if ($hasZero || $moyenne < $validationAverage) {
                $decision = $moyenne < $validationAverage ? 'fail' : 'repeat';
            } else {
                $decision = 'pass';
            }
            \Log::info('Décision calculée', ['decision' => $decision]);

            $academicPath->year_decision = $decision;
            $academicPath->deliberation_date = $dateToUse;
            $academicPath->save();
            $updated++;
            \Log::info('AcademicPath mis à jour', ['id' => $academicPath->id, 'decision' => $decision]);

            // Progression automatique pour les filières non-prépa
            if (!$isPrepa && $decision === 'pass' && $nextYear) {
                $newStudyLevel = $academicPath->study_level + 1;
                $newCohort = ($academicPath->cohort && $academicPath->cohort != 1) ? 1 : $academicPath->cohort;
                
                $newPath = AcademicPath::create([
                    'student_pending_student_id' => $academicPath->student_pending_student_id,
                    'academic_year_id' => $nextYear->id,
                    'study_level' => $newStudyLevel,
                    'role_id' => $academicPath->role_id,
                    'financial_status' => $academicPath->financial_status,
                    'cohort' => $newCohort,
                    'year_decision' => null,
                    'deliberation_date' => null,
                ]);
                
                $progressed++;
                \Log::info('Progression automatique créée', [
                    'new_path_id' => $newPath->id,
                    'old_level' => $academicPath->study_level,
                    'new_level' => $newStudyLevel,
                    'old_cohort' => $academicPath->cohort,
                    'new_cohort' => $newCohort,
                    'next_year_id' => $nextYear->id
                ]);
            }
        }

        \Log::info('=== FIN processYearDeliberationAndProgression ===', ['updated' => $updated, 'progressed' => $progressed]);
        
        return [
            'updated' => $updated,
            'progressed' => $progressed,
            'message' => "$updated décisions mises à jour, $progressed étudiants progressés"
        ];
    }
}

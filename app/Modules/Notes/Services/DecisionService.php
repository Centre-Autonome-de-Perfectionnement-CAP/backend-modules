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
        
        $academicPathQuery = AcademicPath::where('academic_year_id', $academicYearId)
            ->where('study_level', $level);
        
        if ($cohort) {
            $academicPathQuery->where('cohort', $cohort);
        }
        
        $studentPendingStudentIds = $academicPathQuery->pluck('student_pending_student_id')->toArray();
        
        $studentIds = \App\Modules\Inscription\Models\StudentPendingStudent::whereIn('id', $studentPendingStudentIds)
            ->pluck('student_id')->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\StudentGroup::whereIn('student_id', $studentIds)
            ->pluck('class_group_id')->unique()->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\ClassGroup::whereIn('id', $classGroupIds)
            ->where('academic_year_id', $academicYearId)
            ->where('department_id', $departmentId)
            ->where('study_level', $level)
            ->pluck('id')->toArray();
        
        $programsSem1 = Program::whereIn('class_group_id', $classGroupIds)
            ->where('semester', 1)
            ->with('courseElementProfessor.courseElement')
            ->get();

        $programsSem2 = Program::whereIn('class_group_id', $classGroupIds)
            ->where('semester', 2)
            ->with('courseElementProfessor.courseElement')
            ->get();

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

    private function getStudentsForYear($academicYearId, $departmentId, $level, $cohort, $programsSem1, $programsSem2, $hasSem1, $hasSem2, $validationAverage)
    {
        $classGroupIds = collect();
        if ($hasSem1) {
            $classGroupIds = $classGroupIds->merge(Program::whereIn('id', $programsSem1->pluck('id'))->pluck('class_group_id'));
        }
        if ($hasSem2) {
            $classGroupIds = $classGroupIds->merge(Program::whereIn('id', $programsSem2->pluck('id'))->pluck('class_group_id'));
        }
        $classGroupIds = $classGroupIds->unique()->toArray();

        $studentIds = \App\Modules\Inscription\Models\StudentGroup::whereIn('class_group_id', $classGroupIds)
            ->pluck('student_id')->unique()->toArray();

        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.student'
        ])
        ->whereHas('studentPendingStudent.student', function ($q) use ($studentIds) {
            $q->whereIn('id', $studentIds);
        })
        ->where('academic_year_id', $academicYearId)
        ->where('study_level', $level)
        ->where(function ($q) {
            $q->where('year_decision', '!=', 'failed')->orWhereNull('year_decision');
        });

        if ($cohort) {
            $query->where('cohort', $cohort);
        }

        $academicPaths = $query->get();
        
        $allProgramsSem1 = [];
        if ($hasSem1) {
            $allProgramsSem1 = Program::whereIn('class_group_id', $classGroupIds)
                ->where('semester', 1)
                ->with('courseElementProfessor.courseElement')
                ->get();
        }
        
        $allProgramsSem2 = [];
        if ($hasSem2) {
            $allProgramsSem2 = Program::whereIn('class_group_id', $classGroupIds)
                ->where('semester', 2)
                ->with('courseElementProfessor.courseElement')
                ->get();
        }

        return $academicPaths->map(function ($academicPath) use ($programsSem1, $programsSem2, $allProgramsSem1, $allProgramsSem2, $hasSem1, $hasSem2) {
            $studentPending = $academicPath->studentPendingStudent;
            $pendingStudent = $studentPending?->pendingStudent;
            $personalInfo = $pendingStudent?->personalInformation;
            $student = $studentPending?->student;

            $moyennesSem1 = [];
            $moyenneSem1 = 0;
            $countSem1 = 0;
            $hasZeroSem1 = false;
            if ($hasSem1) {
                foreach ($programsSem1 as $program) {
                    $courseId = $program->matiere_professeur->matiere->code ?? null;
                    $grade = null;
                    
                    foreach ($allProgramsSem1 as $p) {
                        $pCourseId = $p->courseElementProfessor->courseElement->code ?? null;
                        if ($courseId === $pCourseId) {
                            $tempGrade = OldSystemGrade::where('student_pending_student_id', $academicPath->student_pending_student_id)
                                ->where('program_id', $p->id)->first();
                            if ($tempGrade && $tempGrade->average > 0) {
                                $grade = $tempGrade;
                                break;
                            } elseif (!$grade) {
                                $grade = $tempGrade;
                            }
                        }
                    }
                    
                    $avg = $grade ? ($grade->average ?? 0) : 0;
                    $moyennesSem1[] = $avg > 0 ? $avg : '-';
                    if ($avg > 0) {
                        $moyenneSem1 += $avg;
                        $countSem1++;
                    }
                    if ($grade && $avg === 0.0 && count($grade->grades ?? []) > 0) {
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
                    $courseId = $program->matiere_professeur->matiere->code ?? null;
                    $grade = null;
                    
                    foreach ($allProgramsSem2 as $p) {
                        $pCourseId = $p->courseElementProfessor->courseElement->code ?? null;
                        if ($courseId === $pCourseId) {
                            $tempGrade = OldSystemGrade::where('student_pending_student_id', $academicPath->student_pending_student_id)
                                ->where('program_id', $p->id)->first();
                            if ($tempGrade && $tempGrade->average > 0) {
                                $grade = $tempGrade;
                                break;
                            } elseif (!$grade) {
                                $grade = $tempGrade;
                            }
                        }
                    }
                    
                    $avg = $grade ? ($grade->average ?? 0) : 0;
                    $moyennesSem2[] = $avg > 0 ? $avg : '-';
                    if ($avg > 0) {
                        $moyenneSem2 += $avg;
                        $countSem2++;
                    }
                    if ($grade && $avg === 0.0 && count($grade->grades ?? []) > 0) {
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
                'id' => $student?->id,
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
        
        $academicPathQuery = AcademicPath::where('academic_year_id', $academicYearId)
            ->where('study_level', $level);
        
        if ($cohort) {
            $academicPathQuery->where('cohort', $cohort);
        }
        
        $studentPendingStudentIds = $academicPathQuery->pluck('student_pending_student_id')->toArray();
        
        $studentIds = \App\Modules\Inscription\Models\StudentPendingStudent::whereIn('id', $studentPendingStudentIds)
            ->pluck('student_id')->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\StudentGroup::whereIn('student_id', $studentIds)
            ->pluck('class_group_id')->unique()->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\ClassGroup::whereIn('id', $classGroupIds)
            ->where('academic_year_id', $academicYearId)
            ->where('department_id', $departmentId)
            ->where('study_level', $level)
            ->pluck('id')->toArray();
        
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

    public function getStudentsBySemester(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, int $semester): array
    {
        \Log::info('DecisionService: Récupération étudiants semestre', compact('academicYearId', 'departmentId', 'level', 'cohort', 'semester'));
        
        $academicPathQuery = AcademicPath::where('academic_year_id', $academicYearId)
            ->where('study_level', $level);
        
        if ($cohort) {
            $academicPathQuery->where('cohort', $cohort);
        }
        
        $studentPendingStudentIds = $academicPathQuery->pluck('student_pending_student_id')->toArray();
        
        if (empty($studentPendingStudentIds)) {
            return [];
        }
        
        $studentIds = \App\Modules\Inscription\Models\StudentPendingStudent::whereIn('id', $studentPendingStudentIds)
            ->pluck('student_id')->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\StudentGroup::whereIn('student_id', $studentIds)
            ->pluck('class_group_id')->unique()->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\ClassGroup::whereIn('id', $classGroupIds)
            ->where('academic_year_id', $academicYearId)
            ->where('department_id', $departmentId)
            ->where('study_level', $level)
            ->pluck('id')->toArray();
        
        if (empty($classGroupIds)) {
            return [];
        }
        
        $programsData = Program::whereIn('class_group_id', $classGroupIds)
            ->where('semester', $semester)
            ->with('courseElementProfessor.courseElement')
            ->get();

        $studentIds = \App\Modules\Inscription\Models\StudentGroup::whereIn('class_group_id', $classGroupIds)
            ->pluck('student_id')
            ->unique()
            ->toArray();

        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.student'
        ])
        ->whereHas('studentPendingStudent.student', function ($q) use ($studentIds) {
            $q->whereIn('id', $studentIds);
        })
        ->where('academic_year_id', $academicYearId)
        ->where('study_level', $level)
        ->where(function ($q) {
            $q->where('year_decision', '!=', 'failed')
              ->orWhereNull('year_decision');
        });

        if ($cohort) {
            $query->where('cohort', $cohort);
        }

        $academicPaths = $query->get();

        return $academicPaths->map(function ($academicPath) use ($programsData, $semester) {
            $studentPending = $academicPath->studentPendingStudent;
            $pendingStudent = $studentPending?->pendingStudent;
            $personalInfo = $pendingStudent?->personalInformation;
            $student = $studentPending?->student;

            $gradeDetails = [];
            $totalCredits = 0;
            $earnedCredits = 0;
            $moyenneSum = 0;
            $programCount = 0;

            foreach ($programsData as $program) {
                $grade = LmdSystemGrade::where('student_pending_student_id', $academicPath->student_pending_student_id)
                    ->where('program_id', $program->id)
                    ->first();

                if ($grade) {
                    $gradesArray = is_array($grade->grades) ? $grade->grades : [];
                    $gradeDetails[$program->id] = [
                        'grades' => $gradesArray,
                        'average' => $grade->average ?? 0
                    ];
                    $moyenneSum += ($grade->average ?? 0);
                    $programCount++;
                    
                    if ($grade->validated) {
                        $earnedCredits += 3;
                    }
                    $totalCredits += 3;
                } else {
                    $gradeDetails[$program->id] = [
                        'grades' => [],
                        'average' => 0
                    ];
                }
            }

            $moyenneGenerale = $programCount > 0 ? $moyenneSum / $programCount : 0;

            return (object)[
                'id' => $student?->id,
                'matricule' => $student?->student_id_number ?? 'N/A',
                'nom' => $personalInfo?->last_name ?? 'N/A',
                'prenoms' => $personalInfo?->first_names ?? 'N/A',
                'moyenne' => round($moyenneGenerale, 2),
                'credits' => $earnedCredits,
                'totalCredits' => $totalCredits,
                'gradeDetails' => $gradeDetails
            ];
        })->filter(function ($item) {
            return $item->nom !== 'N/A' && $item->prenoms !== 'N/A';
        })->sortBy('nom')->values()->toArray();
    }

    public function getStudentsByYear(int $academicYearId, int $departmentId, ?string $level, ?string $cohort): array
    {
        \Log::info('DecisionService: Récupération étudiants année', compact('academicYearId', 'departmentId', 'level', 'cohort'));
        
        $sem1Students = $this->getStudentsBySemester($academicYearId, $departmentId, $level, $cohort, 1);
        $sem2Students = $this->getStudentsBySemester($academicYearId, $departmentId, $level, $cohort, 2);

        $studentsById = [];
        
        foreach ($sem1Students as $student) {
            $studentsById[$student['id']] = [
                'id' => $student['id'],
                'student_id' => $student['student_id'],
                'nom' => $student['nom'],
                'prenom' => $student['prenom'],
                'moyenne_s1' => $student['moyenne'],
                'credits_s1' => $student['credits'],
                'moyenne_s2' => 0,
                'credits_s2' => 0,
                'moyenne_annuelle' => 0,
                'credits_total' => 0
            ];
        }

        foreach ($sem2Students as $student) {
            if (isset($studentsById[$student['id']])) {
                $studentsById[$student['id']]['moyenne_s2'] = $student['moyenne'];
                $studentsById[$student['id']]['credits_s2'] = $student['credits'];
            } else {
                $studentsById[$student['id']] = [
                    'id' => $student['id'],
                    'student_id' => $student['student_id'],
                    'nom' => $student['nom'],
                    'prenom' => $student['prenom'],
                    'moyenne_s1' => 0,
                    'credits_s1' => 0,
                    'moyenne_s2' => $student['moyenne'],
                    'credits_s2' => $student['credits'],
                    'moyenne_annuelle' => 0,
                    'credits_total' => 0
                ];
            }
        }

        foreach ($studentsById as &$student) {
            $student['moyenne_annuelle'] = ($student['moyenne_s1'] + $student['moyenne_s2']) / 2;
            $student['credits_total'] = $student['credits_s1'] + $student['credits_s2'];
        }

        return array_values($studentsById);
    }

    public function getStudentsBySemesterOldSystem(int $academicYearId, int $departmentId, ?string $level, ?string $cohort, int $semester): array
    {
        \Log::info('DecisionService: Récupération étudiants semestre (Old System)', compact('academicYearId', 'departmentId', 'level', 'cohort', 'semester'));
        
        $academicPathQuery = AcademicPath::where('academic_year_id', $academicYearId)
            ->where('study_level', $level);
        
        if ($cohort) {
            $academicPathQuery->where('cohort', $cohort);
        }
        
        $studentPendingStudentIds = $academicPathQuery->pluck('student_pending_student_id')->toArray();
        
        if (empty($studentPendingStudentIds)) {
            return [];
        }
        
        $studentIds = \App\Modules\Inscription\Models\StudentPendingStudent::whereIn('id', $studentPendingStudentIds)
            ->pluck('student_id')->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\StudentGroup::whereIn('student_id', $studentIds)
            ->pluck('class_group_id')->unique()->toArray();
        
        $classGroupIds = \App\Modules\Inscription\Models\ClassGroup::whereIn('id', $classGroupIds)
            ->where('academic_year_id', $academicYearId)
            ->where('department_id', $departmentId)
            ->where('study_level', $level)
            ->pluck('id')->toArray();
        
        if (empty($classGroupIds)) {
            return [];
        }
        
        $programsData = Program::whereIn('class_group_id', $classGroupIds)
            ->where('semester', $semester)
            ->with('courseElementProfessor.courseElement')
            ->get();

        $studentIds = \App\Modules\Inscription\Models\StudentGroup::whereIn('class_group_id', $classGroupIds)
            ->pluck('student_id')
            ->unique()
            ->toArray();

        $query = AcademicPath::with([
            'studentPendingStudent.pendingStudent.personalInformation',
            'studentPendingStudent.student'
        ])
        ->whereHas('studentPendingStudent.student', function ($q) use ($studentIds) {
            $q->whereIn('id', $studentIds);
        })
        ->where('academic_year_id', $academicYearId)
        ->where('study_level', $level)
        ->where(function ($q) {
            $q->where('year_decision', '!=', 'failed')
              ->orWhereNull('year_decision');
        });

        if ($cohort) {
            $query->where('cohort', $cohort);
        }

        $academicPaths = $query->get();

        return $academicPaths->map(function ($academicPath) use ($programsData, $semester) {
            $studentPending = $academicPath->studentPendingStudent;
            $pendingStudent = $studentPending?->pendingStudent;
            $personalInfo = $pendingStudent?->personalInformation;
            $student = $studentPending?->student;

            $gradeDetails = [];
            $moyenneSum = 0;
            $programCount = 0;

            foreach ($programsData as $program) {
                $grade = OldSystemGrade::where('student_pending_student_id', $academicPath->student_pending_student_id)
                    ->where('program_id', $program->id)
                    ->first();

                if ($grade) {
                    $gradesArray = is_array($grade->grades) ? $grade->grades : [];
                    $gradeDetails[$program->id] = [
                        'grades' => $gradesArray,
                        'average' => $grade->average ?? 0
                    ];
                    $moyenneSum += ($grade->average ?? 0);
                    $programCount++;
                } else {
                    $gradeDetails[$program->id] = [
                        'grades' => [],
                        'average' => 0
                    ];
                }
            }

            $moyenneGenerale = $programCount > 0 ? $moyenneSum / $programCount : 0;

            return (object)[
                'id' => $student?->id,
                'matricule' => $student?->student_id_number ?? 'N/A',
                'nom' => $personalInfo?->last_name ?? 'N/A',
                'prenoms' => $personalInfo?->first_names ?? 'N/A',
                'moyenne' => round($moyenneGenerale, 2),
                'gradeDetails' => $gradeDetails
            ];
        })->filter(function ($item) {
            return $item->nom !== 'N/A' && $item->prenoms !== 'N/A';
        })->sortBy('nom')->values()->toArray();
    }

    public function saveSemesterDecisions(array $decisions): int
    {
        $count = 0;
        foreach ($decisions as $decision) {
            $academicPath = AcademicPath::where('student_pending_student_id', $decision['student_pending_student_id'])
                ->first();
            
            if ($academicPath) {
                $academicPath->semester_decision = $decision['semester_decision'];
                $academicPath->save();
                $count++;
            }
        }
        return $count;
    }

    public function saveYearDecisions(array $decisions): int
    {
        $count = 0;
        foreach ($decisions as $decision) {
            $academicPath = AcademicPath::where('student_pending_student_id', $decision['student_pending_student_id'])
                ->first();
            
            if ($academicPath) {
                $academicPath->year_decision = $decision['year_decision'];
                $academicPath->save();
                $count++;
            }
        }
        return $count;
    }
}

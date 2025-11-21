<?php

namespace App\Modules\Notes\Services;

use App\Modules\Notes\Models\LmdSystemGrade;
use App\Modules\Cours\Models\Program;
use Illuminate\Support\Facades\DB;

class LmdGradeService
{
    private GradeCalculationService $calculationService;

    public function __construct(GradeCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Récupère les étudiants d'un programme avec leurs notes
     * 
     * @param Program $program
     * @param string|null $cohort
     * @return \Illuminate\Support\Collection
     */
    public function getStudentsByProgram(Program $program, ?string $cohort = null)
    {
        $classGroup = $program->classGroup;

        // Récupère les academic paths liés à cette classe
        $query = \App\Modules\Inscription\Models\AcademicPath::whereHas('studentPendingStudent', function ($q) use ($classGroup) {
            $q->where('academic_year_id', $classGroup->academic_year_id)
                ->where('study_level', $classGroup->level)
                ->where('year_decision', '!=', 'failed');
        });

        if ($cohort) {
            $query->where('cohort', $cohort);
        }

        $academicPaths = $query->get();

        return $academicPaths->map(function ($academicPath) use ($program) {
            $studentPending = $academicPath->studentPendingStudent;
            $pendingStudent = $studentPending?->pendingStudent;
            $personalInfo = $pendingStudent?->personalInformation;

            // Récupérer la note
            $grade = LmdSystemGrade::where('student_pending_student_id', $academicPath->student_pending_student_id)
                ->where('program_id', $program->id)
                ->first();

            return [
                'student_pending_student_id' => $academicPath->student_pending_student_id,
                'last_name' => $personalInfo?->last_name,
                'first_names' => $personalInfo?->first_names,
                'grades' => $grade?->grades ?? [],
                'average' => $grade?->average,
                'retake_grades' => $grade?->retake_grades ?? [],
                'retake_average' => $grade?->retake_average,
                'validated' => $grade?->validated ?? false,
            ];
        })->filter(function ($item) {
            return $item['last_name'] && $item['first_names'];
        })->sortBy('last_name')->values();
    }

    /**
     * Ajoute une colonne de notes (un nouveau devoir)
     * 
     * @param int $programId
     * @param array $notes ['student_id' => value]
     * @param bool $sessionNormale
     * @return array
     */
    public function addNoteColumn(int $programId, array $notes, bool $sessionNormale = true): array
    {
        $program = Program::findOrFail($programId);
        $results = [];

        DB::beginTransaction();
        try {
            foreach ($notes as $studentPendingStudentId => $note) {
                // Récupère ou crée l'enregistrement
                $grade = LmdSystemGrade::where('student_pending_student_id', $studentPendingStudentId)
                    ->where('program_id', $programId)
                    ->first();

                if ($grade) {
                    // Ajoute la note au tableau existant
                    $gradesArray = $sessionNormale 
                        ? ($grade->grades ?? [])
                        : ($grade->retake_grades ?? []);
                    
                    $gradesArray[] = $note;

                    if ($sessionNormale) {
                        $grade->grades = $gradesArray;
                    } else {
                        $grade->retake_grades = $gradesArray;
                    }
                } else {
                    // Première note
                    $grade = new LmdSystemGrade();
                    $grade->student_pending_student_id = $studentPendingStudentId;
                    $grade->program_id = $programId;
                    
                    if ($sessionNormale) {
                        $grade->grades = [$note];
                    } else {
                        $grade->retake_grades = [$note];
                    }
                }

                // Récupère la pondération actuelle
                $pond = $sessionNormale 
                    ? ($program->weighting ?? [])
                    : ($program->retake_weighting ?? []);

                // Calcule la moyenne si le nombre de notes correspond
                $currentGrades = $sessionNormale ? $grade->grades : $grade->retake_grades;
                if (count($currentGrades) === count($pond) && count($pond) > 0) {
                    $moyenne = $this->calculationService->calculateMoyennePonderee($currentGrades, $pond);
                    
                    if ($sessionNormale) {
                        $grade->average = $moyenne;
                        $grade->validated = $this->calculationService->isValidated($moyenne, $grade->retake_average);
                        $grade->must_retake = $this->calculationService->mustRetake($moyenne);
                    } else {
                        $grade->retake_average = $moyenne;
                        $grade->retaken = true;
                        $grade->validated = $this->calculationService->isValidated($grade->average, $moyenne);
                        $grade->must_retake = $this->calculationService->mustRetake($moyenne);
                    }
                }

                $grade->save();
                $results[] = $grade;
            }

            // Met à jour la pondération du programme (automatique équilibrée)
            if (!empty($results)) {
                $columnCount = count($sessionNormale ? $results[0]->grades : $results[0]->retake_grades);
                $newPond = $this->calculationService->getBalancedPonderation($columnCount);
                
                if ($sessionNormale) {
                    $program->weighting = $newPond;
                } else {
                    $program->retake_weighting = $newPond;
                }
                $program->save();

                // Recalcule les moyennes de TOUS les étudiants avec la nouvelle pondération
                $this->recalculateAllAverages($programId, $sessionNormale);
            }

            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Modifie une note à une position donnée
     * 
     * @param int $studentPendingStudentId
     * @param int $programId
     * @param int $position
     * @param float $note
     * @param bool $sessionNormale
     * @return bool
     */
    public function updateNoteAtPosition(
        int $studentPendingStudentId,
        int $programId,
        int $position,
        float $note,
        bool $sessionNormale = true
    ): bool {
        $grade = LmdSystemGrade::where('student_pending_student_id', $studentPendingStudentId)
            ->where('program_id', $programId)
            ->first();

        if (!$grade) {
            return false;
        }

        $gradesArray = $sessionNormale ? ($grade->grades ?? []) : ($grade->retake_grades ?? []);

        if (!isset($gradesArray[$position])) {
            return false;
        }

        $gradesArray[$position] = $note;

        if ($sessionNormale) {
            $grade->grades = $gradesArray;
        } else {
            $grade->retake_grades = $gradesArray;
        }

        // Recalcule la moyenne
        $program = Program::findOrFail($programId);
        $pond = $sessionNormale ? ($program->weighting ?? []) : ($program->retake_weighting ?? []);
        
        if (count($gradesArray) === count($pond) && count($pond) > 0) {
            $moyenne = $this->calculationService->calculateMoyennePonderee($gradesArray, $pond);
            
            if ($sessionNormale) {
                $grade->average = $moyenne;
                $grade->validated = $this->calculationService->isValidated($moyenne, $grade->retake_average);
                $grade->must_retake = $this->calculationService->mustRetake($moyenne);
            } else {
                $grade->retake_average = $moyenne;
                $grade->validated = $this->calculationService->isValidated($grade->average, $moyenne);
                $grade->must_retake = $this->calculationService->mustRetake($moyenne);
            }
        }

        return $grade->save();
    }

    /**
     * Supprime une colonne de notes (un devoir) pour tous les étudiants
     * 
     * @param int $programId
     * @param int $columnIndex
     * @param bool $sessionNormale
     * @return bool
     */
    public function deleteNoteColumn(int $programId, int $columnIndex, bool $sessionNormale = true): bool
    {
        $program = Program::findOrFail($programId);
        $grades = LmdSystemGrade::where('program_id', $programId)->get();

        $maxNotes = 0;

        foreach ($grades as $grade) {
            $gradesArray = $sessionNormale ? ($grade->grades ?? []) : ($grade->retake_grades ?? []);

            if (isset($gradesArray[$columnIndex])) {
                array_splice($gradesArray, $columnIndex, 1);
            }

            if ($sessionNormale) {
                $grade->grades = $gradesArray;
            } else {
                $grade->retake_grades = $gradesArray;
            }

            // Recalcule la moyenne
            $pond = $this->calculationService->getBalancedPonderation(count($gradesArray));
            $moyenne = $this->calculationService->calculateMoyennePonderee($gradesArray, $pond);

            if ($sessionNormale) {
                $grade->average = $moyenne;
                $grade->validated = $this->calculationService->isValidated($moyenne, $grade->retake_average);
                $grade->must_retake = $this->calculationService->mustRetake($moyenne);
            } else {
                $grade->retake_average = $moyenne;
                $grade->validated = $this->calculationService->isValidated($grade->average, $moyenne);
                $grade->must_retake = $this->calculationService->mustRetake($moyenne);
            }

            $grade->save();

            if (count($gradesArray) > $maxNotes) {
                $maxNotes = count($gradesArray);
            }
        }

        // Met à jour la pondération du programme
        $newPond = $this->calculationService->getBalancedPonderation($maxNotes);
        if ($sessionNormale) {
            $program->weighting = $newPond;
        } else {
            $program->retake_weighting = $newPond;
        }
        $program->save();

        return true;
    }

    /**
     * Définit une pondération manuelle et recalcule toutes les moyennes
     * 
     * @param int $programId
     * @param array $pond
     * @param bool $sessionNormale
     * @return bool
     */
    public function setPonderation(int $programId, array $pond, bool $sessionNormale = true): bool
    {
        // Vérifie que la somme fait 100
        if (array_sum($pond) !== 100) {
            throw new \InvalidArgumentException('La somme de la pondération doit faire 100%');
        }

        $program = Program::findOrFail($programId);
        
        if ($sessionNormale) {
            $program->weighting = $pond;
        } else {
            $program->retake_weighting = $pond;
        }
        $program->save();

        // Recalcule toutes les moyennes
        return $this->recalculateAllAverages($programId, $sessionNormale);
    }

    /**
     * Recalcule les moyennes de tous les étudiants d'un programme
     * 
     * @param int $programId
     * @param bool $sessionNormale
     * @return bool
     */
    private function recalculateAllAverages(int $programId, bool $sessionNormale = true): bool
    {
        $program = Program::findOrFail($programId);
        $pond = $sessionNormale ? ($program->weighting ?? []) : ($program->retake_weighting ?? []);
        $grades = LmdSystemGrade::where('program_id', $programId)->get();

        foreach ($grades as $grade) {
            $gradesArray = $sessionNormale ? ($grade->grades ?? []) : ($grade->retake_grades ?? []);
            
            if (count($gradesArray) === count($pond) && count($pond) > 0) {
                $moyenne = $this->calculationService->calculateMoyennePonderee($gradesArray, $pond);
                
                if ($sessionNormale) {
                    $grade->average = $moyenne;
                    $grade->validated = $this->calculationService->isValidated($moyenne, $grade->retake_average);
                    $grade->must_retake = $this->calculationService->mustRetake($moyenne);
                } else {
                    $grade->retake_average = $moyenne;
                    $grade->validated = $this->calculationService->isValidated($grade->average, $moyenne);
                    $grade->must_retake = $this->calculationService->mustRetake($moyenne);
                }
                
                $grade->save();
            }
        }

        return true;
    }

    /**
     * Obtient les classes d'un professeur regroupées par cycle
     */
    public function getProfessorClassesByCycle(int $professorId, ?int $academicYearId = null, ?int $departmentId = null, ?string $cohort = null): array
    {
        $query = \App\Modules\Inscription\Models\ClassGroup::query()
            ->whereHas('programs.courseElementProfessor', function ($q) use ($professorId) {
                $q->where('professor_id', $professorId);
            })
            ->with(['cycle', 'department', 'programs.courseElementProfessor.courseElement']);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $classes = $query->get();

        return $classes->groupBy('cycle.name')->map(function ($cycleClasses, $cycleName) {
            return [
                'cycle_name' => $cycleName,
                'departments' => $cycleClasses->groupBy('department.name')->map(function ($deptClasses, $deptName) {
                    return [
                        'department_name' => $deptName,
                        'classes' => $deptClasses->map(function ($class) {
                            return [
                                'id' => $class->id,
                                'name' => $class->name,
                                'level' => $class->level,
                                'programs_count' => $class->programs->count()
                            ];
                        })
                    ];
                })->values()
            ];
        })->values()->toArray();
    }

    /**
     * Obtient les programmes d'une classe pour un professeur
     */
    public function getProgramsByClass(int $professorId, int $classGroupId): array
    {
        $classGroup = \App\Modules\Inscription\Models\ClassGroup::with(['department', 'cycle'])
            ->findOrFail($classGroupId);

        $programs = \App\Modules\Cours\Models\Program::where('class_group_id', $classGroupId)
            ->whereHas('courseElementProfessor', function ($query) use ($professorId) {
                $query->where('professor_id', $professorId);
            })
            ->with(['courseElementProfessor.courseElement', 'courseElementProfessor.professor'])
            ->get();

        return [
            'class_group' => [
                'id' => $classGroup->id,
                'name' => $classGroup->name,
                'level' => $classGroup->level,
                'department' => $classGroup->department->name ?? 'N/A',
                'cycle' => $classGroup->cycle->name ?? 'N/A'
            ],
            'programs' => $programs->map(function ($program) {
                return [
                    'id' => $program->id,
                    'uuid' => $program->uuid,
                    'course_name' => $program->courseElementProfessor->courseElement->name ?? 'N/A',
                    'professor_name' => $program->courseElementProfessor->professor->name ?? 'N/A',
                    'weighting' => $program->weighting ?? [],
                    'column_count' => count($program->weighting ?? []),
                    'has_retake' => !empty($program->retake_weighting)
                ];
            })
        ];
    }

    /**
     * Obtient la fiche de notation complète
     */
    public function getGradeSheet(Program $program, ?string $cohort = null): array
    {
        $students = $this->getStudentsByProgram($program, $cohort);
        
        return [
            'program' => [
                'id' => $program->id,
                'uuid' => $program->uuid,
                'name' => $program->courseElementProfessor->courseElement->name ?? 'N/A',
                'class_group' => [
                    'id' => $program->classGroup->id,
                    'name' => $program->classGroup->name ?? 'N/A',
                    'level' => $program->classGroup->level ?? 'N/A',
                ],
                'weighting' => $program->weighting ?? [],
                'retake_weighting' => $program->retake_weighting ?? [],
                'column_count' => count($program->weighting ?? []),
                'retake_column_count' => count($program->retake_weighting ?? [])
            ],
            'students' => $students,
            'total_students' => $students->count(),
            'completed_students' => $students->filter(function ($student) {
                return !in_array(-1, $student['grades'] ?? []);
            })->count()
        ];
    }

    /**
     * Crée une nouvelle évaluation
     */
    public function createEvaluation(int $programId, array $notes, bool $isRetake = false): array
    {
        // Initialiser toutes les notes à -1 par défaut
        $defaultNotes = [];
        $program = Program::findOrFail($programId);
        $students = $this->getStudentsByProgram($program);
        
        foreach ($students as $student) {
            $defaultNotes[$student['student_pending_student_id']] = -1;
        }
        
        // Remplacer par les notes fournies
        foreach ($notes as $studentId => $note) {
            if (isset($defaultNotes[$studentId])) {
                $defaultNotes[$studentId] = $note;
            }
        }
        
        return $this->addNoteColumn($programId, $defaultNotes, !$isRetake);
    }

    /**
     * Duplique une note pour tous les étudiants
     */
    public function duplicateGrade(int $programId, int $position, float $value): array
    {
        $program = Program::findOrFail($programId);
        $students = $this->getStudentsByProgram($program);
        $results = [];
        
        foreach ($students as $student) {
            $success = $this->updateNoteAtPosition(
                $student['student_pending_student_id'],
                $programId,
                $position,
                $value
            );
            
            if ($success) {
                $results[] = $student['student_pending_student_id'];
            }
        }
        
        return ['updated_students' => count($results)];
    }

    /**
     * Exporte la fiche récapitulative
     */
    public function exportGradeSheet(int $programId, bool $includeRetake = false): array
    {
        $program = Program::with(['classGroup', 'courseElementProfessor.courseElement', 'courseElementProfessor.professor'])
            ->findOrFail($programId);
        $students = $this->getStudentsByProgram($program);
        
        $exportData = [
            'program_info' => [
                'name' => $program->courseElementProfessor->courseElement->name ?? 'N/A',
                'class' => $program->classGroup->name ?? 'N/A',
                'professor' => $program->courseElementProfessor->professor->name ?? 'N/A',
                'level' => $program->classGroup->level ?? 'N/A'
            ],
            'weighting' => $program->weighting ?? [],
            'retake_weighting' => $includeRetake ? ($program->retake_weighting ?? []) : [],
            'students' => $students->map(function ($student) use ($includeRetake) {
                $data = [
                    'last_name' => $student['last_name'],
                    'first_names' => $student['first_names'],
                    'grades' => $student['grades'] ?? [],
                    'average' => $student['average']
                ];
                
                if ($includeRetake) {
                    $data['retake_grades'] = $student['retake_grades'] ?? [];
                    $data['retake_average'] = $student['retake_average'];
                    $data['final_average'] = $student['retake_average'] ?? $student['average'];
                }
                
                return $data;
            })
        ];
        
        return $exportData;
    }

    // Méthodes pour l'administration
    public function getTotalEvaluations(?int $academicYearId = null): int
    {
        $query = \App\Modules\Cours\Models\Program::query();
        
        if ($academicYearId) {
            $query->whereHas('classGroup', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }
        
        return $query->whereNotNull('weighting')->count();
    }

    public function getCompletedEvaluations(?int $academicYearId = null): int
    {
        // Évaluations où tous les étudiants ont des notes != -1
        return $this->getTotalEvaluations($academicYearId); // Simplification
    }

    public function getPendingEvaluations(?int $academicYearId = null): int
    {
        return 0; // Simplification
    }

    public function getAverageSuccessRate(?int $academicYearId = null): float
    {
        return 75.5; // Simplification
    }

    public function getProgramsByDepartment(?int $academicYearId = null): array
    {
        return []; // À implémenter
    }

    public function getRecentActivities(?int $academicYearId = null): array
    {
        return []; // À implémenter
    }

    public function getGradesByFilters(?int $academicYearId, ?int $departmentId, ?string $level, ?int $programId, ?string $cohort = null): array
    {
        $query = \App\Modules\Cours\Models\Program::query()
            ->with(['classGroup.department', 'classGroup.cycle', 'courseElementProfessor.courseElement', 'courseElementProfessor.professor']);

        if ($academicYearId) {
            $query->whereHas('classGroup', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            });
        }

        if ($departmentId) {
            $query->whereHas('classGroup', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($level) {
            $query->whereHas('classGroup', function ($q) use ($level) {
                $q->where('level', $level);
            });
        }

        if ($programId) {
            $query->where('id', $programId);
        }

        $programs = $query->get();

        return $programs->map(function ($program) use ($cohort) {
            $students = $this->getStudentsByProgram($program, $cohort);
            
            return [
                'program_id' => $program->id,
                'program_name' => $program->courseElementProfessor->courseElement->name ?? 'N/A',
                'class_name' => $program->classGroup->name ?? 'N/A',
                'department' => $program->classGroup->department->name ?? 'N/A',
                'level' => $program->classGroup->level ?? 'N/A',
                'professor' => $program->courseElementProfessor->professor->name ?? 'N/A',
                'total_students' => $students->count(),
                'students_with_grades' => $students->filter(fn($s) => !empty($s['grades']))->count(),
                'average_class' => $students->avg('average'),
            ];
        })->toArray();
    }

    public function getProgramDetailsForAdmin(int $programId, ?string $cohort = null): array
    {
        $program = Program::with(['classGroup.department', 'classGroup.cycle', 'courseElementProfessor.courseElement', 'courseElementProfessor.professor'])
            ->findOrFail($programId);
        
        return $this->getGradeSheet($program, $cohort);
    }

    public function exportGradesByDepartment(int $academicYearId, int $departmentId, ?string $level, string $format, ?string $cohort = null): array
    {
        $programs = $this->getGradesByFilters($academicYearId, $departmentId, $level, null, $cohort);
        
        return [
            'format' => $format,
            'data' => $programs,
            'filters' => [
                'academic_year_id' => $academicYearId,
                'department_id' => $departmentId,
                'level' => $level,
                'cohort' => $cohort,
            ]
        ];
    }
}

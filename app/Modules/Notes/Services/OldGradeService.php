<?php

namespace App\Modules\Notes\Services;

use App\Modules\Notes\Models\OldSystemGrade;
use App\Modules\Cours\Models\Program;
use Illuminate\Support\Facades\DB;

class OldGradeService
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

        $studentIds = \App\Modules\Inscription\Models\StudentGroup::where('class_group_id', $classGroup->id)
            ->pluck('student_id')
            ->toArray();

        if (empty($studentIds)) {
            return collect([]);
        }

        $studentPendingStudentIds = \App\Modules\Inscription\Models\StudentPendingStudent::whereIn('student_id', $studentIds)
            ->pluck('id')
            ->toArray();

        if (empty($studentPendingStudentIds)) {
            return collect([]);
        }

        $query = \App\Modules\Inscription\Models\AcademicPath::with([
                'studentPendingStudent.pendingStudent.personalInformation'
            ])
            ->whereIn('student_pending_student_id', $studentPendingStudentIds)
            ->where('academic_year_id', $classGroup->academic_year_id)
            ->where(function ($q) {
                $q->where('year_decision', '!=', 'failed')
                  ->orWhereNull('year_decision');
            });

        if ($cohort) {
            $query->where('cohort', $cohort);
        }

        $academicPaths = $query->get();

        return $academicPaths->map(function ($academicPath) use ($program) {
            $studentPending = $academicPath->studentPendingStudent;
            $pendingStudent = $studentPending?->pendingStudent;
            $personalInfo = $pendingStudent?->personalInformation;

            $grade = OldSystemGrade::where('student_pending_student_id', $academicPath->student_pending_student_id)
                ->where('program_id', $program->id)
                ->first();

            return [
                'student_pending_student_id' => $academicPath->student_pending_student_id,
                'last_name' => $personalInfo?->last_name,
                'first_names' => $personalInfo?->first_names,
                'grades' => $grade?->grades ?? [],
                'average' => $grade?->average,
            ];
        })->filter(function ($item) {
            return $item['last_name'] && $item['first_names'];
        })->sortBy('last_name')->values();
    }

    /**
     * Obtient la liste des étudiants d'un programme (sans notes)
     */
    public function getStudentsForEvaluation(Program $program, ?string $cohort = null): array
    {
        $students = $this->getStudentsByProgram($program, $cohort);
        
        return [
            'program' => [
                'id' => $program->id,
                'uuid' => $program->uuid,
                'name' => $program->courseElementProfessor->courseElement->name ?? 'N/A',
                'class_group' => [
                    'id' => $program->classGroup->id,
                    'name' => $program->classGroup->department->name . ' - Niveau ' . $program->classGroup->study_level . ($program->classGroup->group_name ? ' (' . $program->classGroup->group_name . ')' : ''),
                    'level' => $program->classGroup->study_level,
                ],
            ],
            'students' => $students->map(function ($student) {
                return [
                    'student_pending_student_id' => $student['student_pending_student_id'],
                    'last_name' => $student['last_name'],
                    'first_names' => $student['first_names'],
                ];
            })->values(),
        ];
    }

    /**
     * Ajoute une colonne de notes
     * 
     * @param int $programId
     * @param array $notes
     * @return array
     */
    public function addNoteColumn(int $programId, array $notes): array
    {
        $program = Program::findOrFail($programId);
        $results = [];

        DB::beginTransaction();
        try {
            foreach ($notes as $studentPendingStudentId => $note) {
                $grade = OldSystemGrade::where('student_pending_student_id', $studentPendingStudentId)
                    ->where('program_id', $programId)
                    ->first();

                if ($grade) {
                    $gradesArray = $grade->grades ?? [];
                    $gradesArray[] = $note;
                    $grade->grades = $gradesArray;
                } else {
                    $grade = new OldSystemGrade();
                    $grade->student_pending_student_id = $studentPendingStudentId;
                    $grade->program_id = $programId;
                    $grade->grades = [$note];
                }

                $pond = $program->weighting ?? [];

                if (count($grade->grades) === count($pond) && count($pond) > 0) {
                    $grade->average = $this->calculationService->calculateMoyennePonderee($grade->grades, $pond);
                }

                $grade->save();
                $results[] = $grade;
            }

            if (!empty($results)) {
                $columnCount = count($results[0]->grades);
                $program->weighting = $this->calculationService->getBalancedPonderation($columnCount);
                $program->save();

                $this->recalculateAllAverages($programId);
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
     */
    public function updateNoteAtPosition(
        int $studentPendingStudentId,
        int $programId,
        int $position,
        float $note
    ): bool {
        $grade = OldSystemGrade::where('student_pending_student_id', $studentPendingStudentId)
            ->where('program_id', $programId)
            ->first();

        if (!$grade) {
            return false;
        }

        $gradesArray = $grade->grades ?? [];

        if (!isset($gradesArray[$position])) {
            return false;
        }

        $gradesArray[$position] = $note;
        $grade->grades = $gradesArray;

        $program = Program::findOrFail($programId);
        $pond = $program->weighting ?? [];
        
        if (count($gradesArray) === count($pond) && count($pond) > 0) {
            $grade->average = $this->calculationService->calculateMoyennePonderee($gradesArray, $pond);
        }

        return $grade->save();
    }

    /**
     * Supprime une colonne de notes
     */
    public function deleteNoteColumn(int $programId, int $columnIndex): bool
    {
        $program = Program::findOrFail($programId);
        $grades = OldSystemGrade::where('program_id', $programId)->get();

        $maxNotes = 0;

        foreach ($grades as $grade) {
            $gradesArray = $grade->grades ?? [];

            if (isset($gradesArray[$columnIndex])) {
                array_splice($gradesArray, $columnIndex, 1);
            }

            $grade->grades = $gradesArray;

            $pond = $this->calculationService->getBalancedPonderation(count($gradesArray));
            $grade->average = $this->calculationService->calculateMoyennePonderee($gradesArray, $pond);
            $grade->save();

            if (count($gradesArray) > $maxNotes) {
                $maxNotes = count($gradesArray);
            }
        }

        $program->weighting = $this->calculationService->getBalancedPonderation($maxNotes);
        $program->save();

        return true;
    }

    /**
     * Définit une pondération manuelle
     */
    public function setPonderation(int $programId, array $pond): bool
    {
        if (array_sum($pond) !== 100) {
            throw new \InvalidArgumentException('La somme de la pondération doit faire 100%');
        }

        $program = Program::findOrFail($programId);
        $program->weighting = $pond;
        $program->save();

        return $this->recalculateAllAverages($programId);
    }

    /**
     * Recalcule toutes les moyennes
     */
    private function recalculateAllAverages(int $programId): bool
    {
        $program = Program::findOrFail($programId);
        $pond = $program->weighting ?? [];
        $grades = OldSystemGrade::where('program_id', $programId)->get();

        foreach ($grades as $grade) {
            $gradesArray = $grade->grades ?? [];
            
            if (count($gradesArray) === count($pond) && count($pond) > 0) {
                $grade->average = $this->calculationService->calculateMoyennePonderee($gradesArray, $pond);
                $grade->save();
            }
        }

        return true;
    }

    /**
     * Obtient la fiche de notation complète
     */
    public function getGradeSheet(Program $program, ?string $cohort = null): array
    {
        $students = $this->getStudentsByProgram($program, $cohort);
        
        $weighting = $program->weighting ?? [];
        $weightingArray = is_array($weighting) ? array_values($weighting) : [];
        
        return [
            'program' => [
                'id' => $program->id,
                'uuid' => $program->uuid,
                'name' => $program->courseElementProfessor->courseElement->name ?? 'N/A',
                'class_group' => [
                    'id' => $program->classGroup->id,
                    'name' => $program->classGroup->department->name . ' - Niveau ' . $program->classGroup->study_level . ($program->classGroup->group_name ? ' (' . $program->classGroup->group_name . ')' : ''),
                    'level' => $program->classGroup->study_level,
                ],
                'weighting' => $weightingArray,
                'column_count' => count($weightingArray)
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
        $defaultNotes = [];
        $program = Program::findOrFail($programId);
        $students = $this->getStudentsByProgram($program);
        
        foreach ($students as $student) {
            $defaultNotes[$student['student_pending_student_id']] = -1;
        }
        
        foreach ($notes as $studentId => $note) {
            if (isset($defaultNotes[$studentId])) {
                $defaultNotes[$studentId] = $note;
            }
        }
        
        return $this->addNoteColumn($programId, $defaultNotes);
    }

    /**
     * Duplique une colonne de notes complète
     */
    public function duplicateColumn(int $programId, int $columnIndex, bool $sessionNormale = true): array
    {
        $program = Program::findOrFail($programId);
        $grades = OldSystemGrade::where('program_id', $programId)->get();
        
        DB::beginTransaction();
        try {
            $notesToAdd = [];
            
            foreach ($grades as $grade) {
                $gradesArray = $grade->grades ?? [];
                
                if (isset($gradesArray[$columnIndex])) {
                    $notesToAdd[$grade->student_pending_student_id] = $gradesArray[$columnIndex];
                } else {
                    $notesToAdd[$grade->student_pending_student_id] = -1;
                }
            }
            
            $result = $this->addNoteColumn($programId, $notesToAdd);
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

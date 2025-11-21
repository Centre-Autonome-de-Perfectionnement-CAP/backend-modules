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
}

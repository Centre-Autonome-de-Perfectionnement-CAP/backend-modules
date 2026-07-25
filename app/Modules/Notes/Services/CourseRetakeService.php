<?php

namespace App\Modules\Notes\Services;

use App\Modules\Notes\Models\StudentCourseRetake;
use App\Modules\Notes\Models\LmdSystemGrade;
use Illuminate\Support\Facades\DB;

class CourseRetakeService
{
    public function createRetake(array $data): StudentCourseRetake
    {
        return StudentCourseRetake::create($data);
    }

    public function getStudentRetakes(int $studentPendingStudentId, ?int $academicYearId = null)
    {
        $query = StudentCourseRetake::with(['program.courseElementProfessor.courseElement', 'program.classGroup'])
            ->where('student_pending_student_id', $studentPendingStudentId);

        if ($academicYearId) {
            $query->where('retake_academic_year_id', $academicYearId);
        }

        return $query->get()->map(function ($retake) {
            return [
                'id' => $retake->id,
                'uuid' => $retake->uuid,
                'program_id' => $retake->program_id,
                'course_name' => $retake->program->courseElementProfessor->courseElement->name ?? 'N/A',
                'original_level' => $retake->original_study_level,
                'current_level' => $retake->current_study_level,
                'original_year' => $retake->originalAcademicYear->academic_year ?? 'N/A',
                'retake_year' => $retake->retakeAcademicYear->academic_year ?? 'N/A',
                'status' => $retake->status,
                'final_grade' => $retake->final_grade
            ];
        });
    }

    public function updateRetakestatus(int $retakeId, string $status, ?float $finalGrade = null): bool
    {
        $retake = StudentCourseRetake::findOrFail($retakeId);
        $retake->status = $status;
        if ($finalGrade !== null) {
            $retake->final_grade = $finalGrade;
        }
        return $retake->save();
    }

    public function processYearEndRetakes(int $academicYearId, int $classGroupId): array
    {
        $created = [];

        $grades = LmdSystemGrade::whereHas('program.classGroup', function ($q) use ($classGroupId, $academicYearId) {
                $q->where('id', $classGroupId)
                  ->where('academic_year_id', $academicYearId);
            })
            ->with(['program.classGroup'])
            ->get();

        foreach ($grades as $grade) {
            if ($grade->must_retake && !$grade->validated) {
                $academicPath = \App\Modules\Inscription\Models\AcademicPath::where('student_pending_student_id', $grade->student_pending_student_id)
                    ->where('academic_year_id', $academicYearId)
                    ->first();

                if ($academicPath && $academicPath->year_decision === 'passed_with_debt') {
                    $nextAcademicYear = \App\Modules\Inscription\Models\AcademicYear::where('id', '>', $academicYearId)
                        ->orderBy('id')
                        ->first();

                    if ($nextAcademicYear) {
                        $retake = $this->createRetake([
                            'student_pending_student_id' => $grade->student_pending_student_id,
                            'program_id' => $grade->program_id,
                            'original_academic_year_id' => $academicYearId,
                            'retake_academic_year_id' => $nextAcademicYear->id,
                            'original_study_level' => $grade->program->classGroup->study_level,
                            'current_study_level' => (string)((int)$grade->program->classGroup->study_level + 1),
                            'status' => 'pending'
                        ]);
                        $created[] = $retake;
                    }
                }
            }
        }

        return $created;
    }
}

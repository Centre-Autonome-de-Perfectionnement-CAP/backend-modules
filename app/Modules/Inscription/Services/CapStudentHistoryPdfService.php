<?php

namespace App\Modules\Inscription\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CapStudentHistoryPdfService
{
    public function download()
    {
        $groups = $this->getGroupedStudents();
        $totalStudents = $groups->sum(fn (array $group) => $group['students']->count());

        return Pdf::loadView('inscription::pdfs.cap-student-history', [
            'groups' => $groups,
            'totalStudents' => $totalStudents,
            'generatedAt' => now()->format('d/m/Y à H:i'),
        ])
            ->setPaper('a4', 'landscape')
            ->download('liste_etudiants_cap_depuis_2023_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Regroupe les parcours de première et deuxième année depuis 2023.
     */
    public function getGroupedStudents(): Collection
    {
        $students = DB::table('academic_paths')
            ->join('academic_years', 'academic_years.id', '=', 'academic_paths.academic_year_id')
            ->join('student_pending_student', 'student_pending_student.id', '=', 'academic_paths.student_pending_student_id')
            ->join('students', 'students.id', '=', 'student_pending_student.student_id')
            ->join('pending_students', 'pending_students.id', '=', 'student_pending_student.pending_student_id')
            ->join('personal_information', 'personal_information.id', '=', 'pending_students.personal_information_id')
            ->join('departments', 'departments.id', '=', 'pending_students.department_id')
            ->where('academic_years.year_start', '>=', '2023-01-01')
            ->whereNull('academic_paths.deleted_at')
            ->whereNull('academic_years.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('pending_students.deleted_at')
            ->whereNull('personal_information.deleted_at')
            ->select([
                'academic_years.id as academic_year_id',
                'academic_years.academic_year',
                'academic_years.year_start',
                'departments.id as department_id',
                'departments.name as department_name',
                'academic_paths.study_level',
                'pending_students.level as registered_level',
                'students.id as student_id',
                'personal_information.last_name',
                'personal_information.first_names',
                'personal_information.birth_date',
                'personal_information.birth_place',
            ])
            ->orderBy('academic_years.year_start')
            ->orderBy('departments.name')
            ->orderBy('personal_information.last_name')
            ->orderBy('personal_information.first_names')
            ->get()
            ->map(function (object $student): ?object {
                $level = $this->levelNumber($student->study_level ?: $student->registered_level);

                if ($level === null) {
                    return null;
                }

                $student->level_number = $level;

                return $student;
            })
            ->filter()
            ->unique(fn (object $student) => implode(':', [
                $student->student_id,
                $student->academic_year_id,
                $student->department_id,
                $student->level_number,
            ]));

        return $students
            ->groupBy(fn (object $student) => $student->academic_year_id.':'.$student->department_id)
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'academic_year' => $first->academic_year,
                    'department_name' => $first->department_name,
                    'students' => $group->values(),
                ];
            })
            ->values();
    }

    private function levelNumber(?string $level): ?int
    {
        if ($level === null) {
            return null;
        }

        $level = trim($level);
        if (preg_match('/^(?:(?:niveau|licence|master|ing[ée]nieur|sp[ée]cialit[ée]|[lmi])\s*)?([12])(?:\s*(?:er|re|e|ere|ère|eme|ème))?(?:\s+ann[ée]e)?$/iu', $level, $matches)) {
            return (int) $matches[1];
        }

        return match (mb_strtolower($level)) {
            'première année', 'premiere annee' => 1,
            'deuxième année', 'deuxieme annee' => 2,
            default => null,
        };
    }
}

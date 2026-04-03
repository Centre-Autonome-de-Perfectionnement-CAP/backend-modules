<?php

namespace App\Modules\Inscription\Services;

use App\Services\DatabaseAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\Core\Services\PdfService;
use App\Modules\Core\Services\NationalityService;

class StudentService
{
    public function getAll(array $filters = [], int $perPage = 10)
    {
        $query = DB::table('pending_students')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->join('academic_years', 'pending_students.academic_year_id', '=', 'academic_years.id')
            ->join('student_pending_student', 'pending_students.id', '=', 'student_pending_student.pending_student_id')
            ->leftJoin('entry_diplomas', 'pending_students.entry_diploma_id', '=', 'entry_diplomas.id')
            ->leftJoin('student_groups', 'student_pending_student.student_id', '=', 'student_groups.student_id')
            ->leftJoin('class_groups', function ($join) {
                $join->on('student_groups.class_group_id', '=', 'class_groups.id')
                    ->on('class_groups.academic_year_id', '=', 'pending_students.academic_year_id')
                    ->on('class_groups.department_id', '=', 'pending_students.department_id')
                    ->on('class_groups.study_level', '=', 'pending_students.level');
            })
            ->select(
                'pending_students.id',
                'student_pending_student.id as student_pending_student_id',
                'student_pending_student.student_id',
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                'personal_information.gender as sexe',
                'personal_information.birth_date as dateNaissance',
                'departments.name as filiere',
                'pending_students.level as niveau',
                'academic_years.academic_year as annee',
                'entry_diplomas.name as entryDiploma',
                'pending_students.status as statut',
                'personal_information.email',
                DB::raw(DatabaseAdapter::jsonExtract('personal_information.contacts', '$.phone') . ' as telephone'),
                DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id) as matricule"),
                DB::raw("(SELECT fingerprint_status FROM students WHERE students.id = student_pending_student.student_id) as fingerprint_status"),
                'class_groups.group_name as groupe'
            )
            ->where('pending_students.status', '!=', 'pending');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names'])), 'like', "%{$search}%")
                    ->orWhere(DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id)"), 'like', "%{$search}%");
            });
        }

        $query->orderBy('personal_information.last_name')
            ->orderBy('personal_information.first_names');

        $results = $query->paginate($perPage);

        $results->getCollection()->transform(function ($student) {
            $student->redoublant = ($student->niveau && $this->isRepeatingStudent($student->student_pending_student_id, $student->niveau)) ? 'Oui' : 'Non';
            return $student;
        });

        return $results;
    }

    public function getById(int $id)
    {
        $student = DB::table('pending_students')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->join('departments', 'pending_students.department_id', '=', 'departments.id')
            ->join('academic_years', 'pending_students.academic_year_id', '=', 'academic_years.id')
            ->join('student_pending_student', 'pending_students.id', '=', 'student_pending_student.pending_student_id')
            ->select(
                'pending_students.id',
                'student_pending_student.id as student_pending_student_id',
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                DB::raw("(SELECT student_id_number FROM students WHERE students.id = student_pending_student.student_id) as matricule"),
                DB::raw("(SELECT fingerprint_status FROM students WHERE students.id = student_pending_student.student_id) as fingerprint_status")
            )
            ->where('pending_students.id', $id)
            ->first();

        return $student;
    }

    public function isRepeatingStudent(?int $studentPendingStudentId, string $level): bool
    {
        if (!$studentPendingStudentId) return false;

        $count = DB::table('academic_paths')
            ->where('student_pending_student_id', $studentPendingStudentId)
            ->where('study_level', $level)
            ->distinct()
            ->count('academic_year_id');

        return $count > 1;
    }

    public function updateFingerprintStatus(int $studentId, bool $status)
    {
        try {
            DB::table('students')
                ->where('id', $studentId)
                ->update([
                    'fingerprint_status' => $status,
                    'updated_at' => now()
                ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour fingerprint', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function getAllForExport(array $filters = [])
    {
        $query = DB::table('students')
            ->join('student_pending_student', 'students.id', '=', 'student_pending_student.student_id')
            ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
            ->join('personal_information', 'pending_students.personal_information_id', '=', 'personal_information.id')
            ->select(
                'students.id',
                'students.student_id_number as matricule',
                DB::raw(DatabaseAdapter::concat(['personal_information.last_name', "' '", 'personal_information.first_names']) . ' as nomPrenoms'),
                'students.fingerprint_status'
            );

        // ✅ CORRECTION ICI
        if (isset($filters['fingerprint_status'])) {
            $query->where('students.fingerprint_status', $filters['fingerprint_status']);
        }

        return $query->get();
    }
}
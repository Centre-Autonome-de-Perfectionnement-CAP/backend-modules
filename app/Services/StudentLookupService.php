<?php

namespace App\Services;

use App\Models\LegacyStudent;
use App\Modules\Inscription\Models\Student;

/**
 * Moteur de recherche cascade : students (>= 2023) -> legacy_students (< 2023).
 *
 * Contrat validé avec Dev 2 (Yannick) :
 *
 *  Trouvé (students)        : ['found' => true,  'source' => 'main_students',   'student' => [...]]
 *  Trouvé (legacy_students)  : ['found' => true,  'source' => 'legacy_students', 'student' => [..., 'status', 'departments']]
 *  Pas trouvé                : ['found' => false, 'source' => null, 'student' => null, 'error_code' => 'STUDENT_NOT_FOUND']
 */
class StudentLookupService
{
    public function lookup(string $matricule): array
    {
        $mainStudent = Student::query()
            ->where('student_id_number', $matricule)
            ->first();

        if ($mainStudent) {
            return [
                'found' => true,
                'source' => 'main_students',
                'student' => $this->formatMainStudent($mainStudent),
            ];
        }

        $legacyStudent = LegacyStudent::query()
            ->with('departments')
            ->where('matricule', $matricule)
            ->first();

        if ($legacyStudent) {
            return [
                'found' => true,
                'source' => 'legacy_students',
                'student' => $this->formatLegacyStudent($legacyStudent),
            ];
        }

        return [
            'found' => false,
            'source' => null,
            'student' => null,
            'error_code' => 'STUDENT_NOT_FOUND',
        ];
    }

    /**
     * `students` ne stocke que student_id_number/password/uuid. Les infos
     * nominatives viennent de l'accesseur Student::personal_information,
     * lui-même résolu via le premier PendingStudent rattaché — donc
     * potentiellement null si l'étudiant n'a pas (ou plus) de dossier lié.
     * On protège chaque champ avec `?->` pour ce cas.
     */
    private function formatMainStudent(Student $student): array
    {
        $personalInfo = $student->personal_information;

        return [
            'id' => $student->id,
            'matricule' => $student->student_id_number,
            'first_name' => $personalInfo?->first_names,
            'last_name' => $personalInfo?->last_name,
            'email' => $personalInfo?->email,
        ];
    }

    private function formatLegacyStudent(LegacyStudent $student): array
    {
        return [
            'id' => $student->id,
            'matricule' => $student->matricule,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'email' => $student->email,
            'status' => $student->status,
            'departments' => $student->departments->map(fn ($department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])->all(),
        ];
    }
}

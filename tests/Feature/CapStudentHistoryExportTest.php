<?php

namespace Tests\Feature;

use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Services\CapStudentHistoryPdfService;
use Tests\TestCase;

class CapStudentHistoryExportTest extends TestCase
{
    public function test_export_requires_authentication(): void
    {
        $this->get('/api/inscription/students/export/cap-history')
            ->assertUnauthorized();
    }

    public function test_export_groups_registered_first_and_second_year_students_since_2023(): void
    {
        $this->authenticatedUser();

        $department = Department::factory()->create(['name' => 'Génie Civil']);

        $this->createEnrollment($department, '2023-2024', '2023-09-01', 'L1');
        $this->createEnrollment($department, '2024-2025', '2024-09-01', '2');
        $this->createEnrollment($department, '2022-2023', '2022-09-01', '1');
        $this->createEnrollment($department, '2025-2026', '2025-09-01', 'L3');

        $groups = app(CapStudentHistoryPdfService::class)->getGroupedStudents();

        $this->assertCount(2, $groups);
        $this->assertSame(['2023-2024', '2024-2025'], $groups->pluck('academic_year')->all());
        $this->assertSame([1, 2], $groups->map(
            fn (array $group) => $group['students']->first()->level_number
        )->all());

        $response = $this->get('/api/inscription/students/export/cap-history');

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function createEnrollment(
        Department $department,
        string $academicYearLabel,
        string $yearStart,
        string $level
    ): void {
        $academicYear = AcademicYear::factory()->create([
            'academic_year' => $academicYearLabel,
            'year_start' => $yearStart,
        ]);
        $personalInformation = PersonalInformation::factory()->create([
            'last_name' => 'CAP',
            'first_names' => 'Étudiant '.$level,
            'birth_date' => '2000-01-15',
            'birth_place' => 'Abomey-Calavi',
        ]);
        $pendingStudent = PendingStudent::factory()->create([
            'personal_information_id' => $personalInformation->id,
            'department_id' => $department->id,
            'academic_year_id' => $academicYear->id,
            'level' => $level,
            'status' => 'approved',
        ]);
        $student = Student::factory()->create();
        $studentPendingStudent = StudentPendingStudent::create([
            'student_id' => $student->id,
            'pending_student_id' => $pendingStudent->id,
        ]);

        AcademicPath::create([
            'student_pending_student_id' => $studentPendingStudent->id,
            'academic_year_id' => $academicYear->id,
            'study_level' => $level,
        ]);
    }
}

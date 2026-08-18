<?php

namespace Tests\Unit\Services;

use App\Models\LegacyStudent;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Student;
use App\Services\StudentLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentLookupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StudentLookupService::class);
    }

    public function test_it_finds_a_student_in_main_students(): void
    {
        $student = Student::factory()->create(['student_id_number' => 'MAIN-TEST-001']);

        $result = $this->service->lookup('MAIN-TEST-001');

        $this->assertTrue($result['found']);
        $this->assertSame('main_students', $result['source']);
        $this->assertSame($student->id, $result['student']['id']);
        $this->assertSame('MAIN-TEST-001', $result['student']['matricule']);
    }

    /**
     * Un Student sans PendingStudent rattaché a personal_information === null.
     * Le service ne doit pas planter, juste renvoyer des champs nominatifs
     * à null plutôt qu'une erreur "Attempt to read property on null".
     */
    public function test_it_handles_a_main_student_without_personal_information(): void
    {
        Student::factory()->create(['student_id_number' => 'MAIN-TEST-NOINFO']);

        $result = $this->service->lookup('MAIN-TEST-NOINFO');

        $this->assertTrue($result['found']);
        $this->assertSame('main_students', $result['source']);
        $this->assertNull($result['student']['first_name']);
        $this->assertNull($result['student']['last_name']);
        $this->assertNull($result['student']['email']);
    }

    public function test_it_finds_a_student_in_legacy_students_with_status_and_departments(): void
    {
        $department = Department::factory()->create();

        $legacyStudent = LegacyStudent::query()->create([
            'matricule' => 'LEGACY-TEST-001',
            'first_name' => 'Test',
            'last_name' => 'Legacy',
            'email' => 'test.legacy@example.com',
            'enrollment_year' => 2019,
            'status' => 'validated',
        ]);
        $legacyStudent->departments()->attach($department->id);

        $result = $this->service->lookup('LEGACY-TEST-001');

        $this->assertTrue($result['found']);
        $this->assertSame('legacy_students', $result['source']);
        $this->assertSame('validated', $result['student']['status']);
        $this->assertCount(1, $result['student']['departments']);
        $this->assertSame($department->id, $result['student']['departments'][0]['id']);
        $this->assertSame($department->name, $result['student']['departments'][0]['name']);
    }

    public function test_it_returns_not_found_for_unknown_matricule(): void
    {
        $result = $this->service->lookup('DOES-NOT-EXIST-999');

        $this->assertFalse($result['found']);
        $this->assertNull($result['source']);
        $this->assertNull($result['student']);
        $this->assertSame('STUDENT_NOT_FOUND', $result['error_code']);
    }

    public function test_scopes_and_status_badge_accessor(): void
    {
        LegacyStudent::query()->create([
            'matricule' => 'SCOPE-TEST-001',
            'first_name' => 'Pending',
            'last_name' => 'One',
            'email' => 'pending.one@example.com',
            'enrollment_year' => 2020,
            'status' => 'pending',
        ]);

        $pending = LegacyStudent::pending()->where('matricule', 'SCOPE-TEST-001')->first();
        $this->assertNotNull($pending);
        $this->assertSame('orange', $pending->status_badge['color']);

        $searched = LegacyStudent::search('SCOPE-TEST-001')->first();
        $this->assertNotNull($searched);
        $this->assertSame('Pending One', $searched->full_name);
    }
}

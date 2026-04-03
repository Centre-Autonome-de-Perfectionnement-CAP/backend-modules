<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Modules\Inscription\Services\StudentIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentIdServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StudentIdService $studentIdService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->studentIdService = new StudentIdService();
    }

    /** @test */
    public function it_generates_student_id_with_correct_format()
    {
        $studentId = $this->studentIdService->generateStudentId('STD');

        // Format attendu: STD2024XXXXX (où XXXXX est un nombre de 5 chiffres)
        $this->assertMatchesRegularExpression('/^STD\d{9}$/', $studentId);
        $this->assertTrue(strlen($studentId) == 12); // STD + 4 chiffres année + 5 chiffres
    }

    /** @test */
    public function it_generates_unique_student_ids()
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $this->studentIdService->generateStudentId('TST');
        }

        // Tous les IDs doivent être uniques
        $uniqueIds = array_unique($ids);
        $this->assertCount(10, $uniqueIds);
    }

    /** @test */
    public function it_includes_academic_year_in_student_id()
    {
        $studentId = $this->studentIdService->generateStudentId('STD');
        $currentYear = date('Y');

        $this->assertStringContainsString($currentYear, $studentId);
    }

    /** @test */
    public function it_validates_student_id_length()
    {
        // Test avec un ID trop court
        $shortId = '1234567';
        $this->assertFalse($this->studentIdService->validateStudentId($shortId));

        // Test avec un ID de longueur valide (mais qui n'existe pas en DB)
        // Ce test passera false car l'ID n'existe pas en base
        $longId = '12345678901234';
        $this->assertFalse($this->studentIdService->validateStudentId($longId));
    }

    /** @test */
    public function it_generates_id_with_custom_prefix()
    {
        $studentId = $this->studentIdService->generateStudentId('CAP');

        $this->assertStringStartsWith('CAP', $studentId);
    }

    /** @test */
    public function it_generates_id_with_default_prefix()
    {
        $studentId = $this->studentIdService->generateStudentId();

        $this->assertStringStartsWith('STD', $studentId);
    }
}

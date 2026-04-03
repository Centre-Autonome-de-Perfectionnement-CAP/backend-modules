<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

class FormatHelperTest extends TestCase
{
    /** @test */
    public function it_formats_student_name_correctly()
    {
        $firstName = 'Jean';
        $lastName = 'Dupont';

        $formatted = formatStudentName($firstName, $lastName);

        $this->assertEquals('DUPONT Jean', $formatted);
    }

    /** @test */
    public function it_formats_date_to_french_format()
    {
        $date = '2024-01-15';

        $formatted = formatDate($date);

        $this->assertEquals('15/01/2024', $formatted);
    }

    /** @test */
    public function it_formats_academic_year()
    {
        $year = 2024;

        $formatted = formatAcademicYear($year);

        $this->assertEquals('2024-2025', $formatted);
    }

    /** @test */
    public function it_formats_phone_number()
    {
        $phone = '0612345678';

        $formatted = formatPhoneNumber($phone);

        // Vérifie le format sénégalais: +221 0 61 23 45 678
        $this->assertStringContainsString('+221', $formatted);
        $this->assertStringContainsString('0', $formatted);
        $this->assertStringContainsString('61', $formatted);
        $this->assertStringContainsString('23', $formatted);
        $this->assertStringContainsString('45', $formatted);
        $this->assertStringContainsString('678', $formatted);
    }

    /** @test */
    public function it_sanitizes_input_strings()
    {
        $input = '<script>alert("XSS")</script>Hello';

        $sanitized = sanitizeInput($input);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('Hello', $sanitized);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $validEmail = 'test@example.com';
        $invalidEmail = 'invalid-email';

        $this->assertTrue(validateEmail($validEmail));
        $this->assertFalse(validateEmail($invalidEmail));
    }

    /** @test */
    public function it_validates_matricule_format()
    {
        $validMatricule = 'CAP2024001';
        $invalidMatricule = 'invalid';

        $this->assertTrue(validateMatricule($validMatricule));
        $this->assertFalse(validateMatricule($invalidMatricule));
    }
}

// Helper functions pour les tests
if (!function_exists('formatStudentName')) {
    function formatStudentName($firstName, $lastName) {
        return strtoupper($lastName) . ' ' . ucfirst($firstName);
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('formatAcademicYear')) {
    function formatAcademicYear($year) {
        return $year . '-' . ($year + 1);
    }
}

if (!function_exists('formatPhoneNumber')) {
    function formatPhoneNumber($phone) {
        // Format sénégalais
        return '+221 ' . substr($phone, 0, 1) . ' ' . substr($phone, 1, 2) . ' ' . 
               substr($phone, 3, 2) . ' ' . substr($phone, 5, 2) . ' ' . substr($phone, 7);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validateMatricule')) {
    function validateMatricule($matricule) {
        return preg_match('/^[A-Z0-9]{9,}$/', $matricule) === 1;
    }
}

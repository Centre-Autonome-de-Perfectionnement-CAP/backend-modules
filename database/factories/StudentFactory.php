<?php

namespace Database\Factories;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inscription\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id_number' => 'STU' . fake()->unique()->numerify('######'),
            'password' => bcrypt('password'),
        ];
    }
}

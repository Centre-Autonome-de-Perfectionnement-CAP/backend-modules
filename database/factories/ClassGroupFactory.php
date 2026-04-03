<?php

namespace Database\Factories;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inscription\Models\ClassGroup>
 */
class ClassGroupFactory extends Factory
{
    protected $model = ClassGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_name' => 'Groupe ' . fake()->randomElement(['A', 'B', 'C', 'D', '1', '2', '3']),
            'department_id' => Department::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'study_level' => fake()->randomElement(['L1', 'L2', 'L3', 'M1', 'M2']),
        ];
    }
}

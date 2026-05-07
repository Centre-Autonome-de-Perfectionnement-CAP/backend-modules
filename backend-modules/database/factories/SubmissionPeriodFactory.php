<?php

namespace Database\Factories;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\SubmissionPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inscription\Models\SubmissionPeriod>
 */
class SubmissionPeriodFactory extends Factory
{
    protected $model = SubmissionPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');
        $endDate = (clone $startDate)->modify('+2 months');

        return [
            'academic_year_id' => AcademicYear::factory(),
            'department_id' => Department::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'type' => fake()->randomElement(['inscription', 'reclamation', 'reinscription']),
            'is_active' => fake()->boolean(70), // 70% de chance d'être actif
        ];
    }
}

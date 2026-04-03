<?php

namespace Database\Factories;

use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inscription\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Informatique',
            'Mathématiques',
            'Physique',
            'Chimie',
            'Génie Civil',
            'Génie Électrique',
            'Génie Mécanique',
        ]);
        
        return [
            'name' => $name,
            'abbreviation' => strtoupper(substr($name, 0, 3)),
            'cycle_id' => Cycle::factory(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}

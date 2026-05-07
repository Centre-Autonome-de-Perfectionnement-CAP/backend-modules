<?php

namespace Database\Factories;

use App\Modules\RH\Models\Professor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\RH\Models\Professor>
 */
class ProfessorFactory extends Factory
{
    protected $model = Professor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'password' => bcrypt('password'),
            'status' => 'active',
        ];
    }
}

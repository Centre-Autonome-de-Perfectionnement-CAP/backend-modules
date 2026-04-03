<?php

namespace Database\Factories;

use App\Modules\Inscription\Models\PersonalInformation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inscription\Models\PersonalInformation>
 */
class PersonalInformationFactory extends Factory
{
    protected $model = PersonalInformation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_names' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'contacts' => [fake()->phoneNumber()],
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'birth_place' => fake()->city(),
            'birth_country' => fake()->country(),
            'nationality' => fake()->randomElement(['Béninoise', 'Togolaise', 'Burkinabè', 'Ivoirienne']),
            'gender' => fake()->randomElement(['M', 'F']),
        ];
    }
}

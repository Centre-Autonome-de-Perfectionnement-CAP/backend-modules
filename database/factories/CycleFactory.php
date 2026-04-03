<?php

namespace Database\Factories;

use App\Modules\Inscription\Models\Cycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inscription\Models\Cycle>
 */
class CycleFactory extends Factory
{
    protected $model = Cycle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;
        
        $types = ['Licence', 'Master', 'Ingénieur', 'Doctorat', 'BTS', 'DUT'];
        $baseName = fake()->randomElement($types);
        $name = $baseName . ' ' . $counter . ' ' . fake()->word();
        
        return [
            'name' => $name,
            'abbreviation' => strtoupper(substr($baseName, 0, 1)),
            'years_count' => fake()->numberBetween(3, 5),
            'is_lmd' => true,
            'type' => fake()->randomElement(['licence', 'master', 'ingenierie']),
        ];
    }
}

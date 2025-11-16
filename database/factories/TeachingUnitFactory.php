<?php

namespace Database\Factories;

use App\Modules\Cours\Models\TeachingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Cours\Models\TeachingUnit>
 */
class TeachingUnitFactory extends Factory
{
    protected $model = TeachingUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'Mathématiques Fondamentales',
            'Algorithmique et Structures de Données',
            'Bases de Données',
            'Réseaux et Télécommunications',
            'Programmation Orientée Objet',
            'Systèmes d\'Exploitation',
            'Intelligence Artificielle',
        ];

        $name = fake()->randomElement($names);
        
        return [
            'name' => $name,
            'code' => 'UE-' . strtoupper(fake()->unique()->lexify('???')),
        ];
    }
}

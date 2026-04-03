<?php

namespace Database\Factories;

use App\Modules\EmploiDuTemps\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\EmploiDuTemps\Models\Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buildingNames = [
            'Bâtiment Sciences',
            'Bâtiment Arts',
            'Bâtiment Technologie',
            'Bâtiment Principal',
            'Bâtiment A',
            'Bâtiment B',
            'Bâtiment C',
        ];

        $name = fake()->randomElement($buildingNames);
        
        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->lexify('B??')),
            'address' => fake()->address(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Bâtiment inactif
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

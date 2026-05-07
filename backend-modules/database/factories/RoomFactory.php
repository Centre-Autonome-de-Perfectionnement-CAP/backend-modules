<?php

namespace Database\Factories;

use App\Modules\EmploiDuTemps\Models\Room;
use App\Modules\EmploiDuTemps\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\EmploiDuTemps\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            Room::TYPE_AMPHITHEATER,
            Room::TYPE_CLASSROOM,
            Room::TYPE_LAB,
            Room::TYPE_COMPUTER_LAB,
            Room::TYPE_CONFERENCE,
        ];

        $type = fake()->randomElement($types);
        $roomNumber = fake()->numberBetween(100, 999);
        
        $capacities = [
            Room::TYPE_AMPHITHEATER => fake()->numberBetween(150, 500),
            Room::TYPE_CLASSROOM => fake()->numberBetween(30, 60),
            Room::TYPE_LAB => fake()->numberBetween(20, 40),
            Room::TYPE_COMPUTER_LAB => fake()->numberBetween(25, 50),
            Room::TYPE_CONFERENCE => fake()->numberBetween(50, 100),
        ];

        return [
            'building_id' => Building::factory(),
            'name' => match($type) {
                Room::TYPE_AMPHITHEATER => 'Amphi ' . fake()->randomElement(['A', 'B', 'C', 'D']),
                Room::TYPE_CLASSROOM => 'Salle ' . $roomNumber,
                Room::TYPE_LAB => 'Laboratoire ' . $roomNumber,
                Room::TYPE_COMPUTER_LAB => 'Salle Info ' . $roomNumber,
                Room::TYPE_CONFERENCE => 'Salle Conférence ' . fake()->randomElement(['A', 'B']),
            },
            'code' => strtoupper(fake()->unique()->lexify('R???-') . $roomNumber),
            'capacity' => $capacities[$type],
            'room_type' => $type,
            'equipment' => fake()->randomElements([
                'projecteur',
                'climatisation',
                'tableau_blanc',
                'tableau_interactif',
                'microphones',
                'ordinateurs',
                'connexion_internet',
            ], fake()->numberBetween(2, 5)),
            'is_available' => true,
        ];
    }

    /**
     * Salle indisponible
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Amphithéâtre
     */
    public function amphitheater(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_type' => Room::TYPE_AMPHITHEATER,
            'capacity' => fake()->numberBetween(150, 500),
        ]);
    }

    /**
     * Salle de classe
     */
    public function classroom(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_type' => Room::TYPE_CLASSROOM,
            'capacity' => fake()->numberBetween(30, 60),
        ]);
    }

    /**
     * Salle informatique
     */
    public function computerLab(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_type' => Room::TYPE_COMPUTER_LAB,
            'capacity' => fake()->numberBetween(25, 50),
        ]);
    }
}

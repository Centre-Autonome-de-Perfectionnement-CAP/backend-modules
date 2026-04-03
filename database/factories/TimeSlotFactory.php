<?php

namespace Database\Factories;

use App\Modules\EmploiDuTemps\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\EmploiDuTemps\Models\TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $days = [
            TimeSlot::DAY_MONDAY,
            TimeSlot::DAY_TUESDAY,
            TimeSlot::DAY_WEDNESDAY,
            TimeSlot::DAY_THURSDAY,
            TimeSlot::DAY_FRIDAY,
        ];

        $types = [
            TimeSlot::TYPE_LECTURE,
            TimeSlot::TYPE_TD,
            TimeSlot::TYPE_TP,
        ];

        $timeSlots = [
            ['08:00', '10:00', 'Matinée - Bloc 1'],
            ['10:15', '12:15', 'Matinée - Bloc 2'],
            ['13:00', '15:00', 'Après-midi - Bloc 1'],
            ['15:15', '17:15', 'Après-midi - Bloc 2'],
            ['17:30', '19:30', 'Soirée'],
        ];

        $slot = fake()->randomElement($timeSlots);

        return [
            'day_of_week' => fake()->randomElement($days),
            'start_time' => $slot[0],
            'end_time' => $slot[1],
            'type' => fake()->randomElement($types),
            'name' => $slot[2],
        ];
    }

    /**
     * Créneau du lundi
     */
    public function monday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => TimeSlot::DAY_MONDAY,
        ]);
    }

    /**
     * Cours magistral
     */
    public function lecture(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TimeSlot::TYPE_LECTURE,
        ]);
    }

    /**
     * TD
     */
    public function td(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TimeSlot::TYPE_TD,
        ]);
    }

    /**
     * TP
     */
    public function tp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TimeSlot::TYPE_TP,
        ]);
    }

    /**
     * Examen
     */
    public function exam(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TimeSlot::TYPE_EXAM,
        ]);
    }
}

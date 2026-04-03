<?php

namespace Database\Factories;

use App\Modules\Cours\Models\CourseElement;
use App\Modules\Cours\Models\TeachingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Cours\Models\CourseElement>
 */
class CourseElementFactory extends Factory
{
    protected $model = CourseElement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $courses = [
            ['Programmation Web', 'WEB', 6],
            ['Base de Données Avancées', 'BDA', 6],
            ['Réseaux Informatiques', 'RES', 4],
            ['Systèmes Distribués', 'SYD', 6],
            ['Machine Learning', 'ML', 6],
            ['Développement Mobile', 'MOB', 4],
            ['Architecture Logicielle', 'ARC', 6],
            ['Sécurité Informatique', 'SEC', 4],
        ];

        $course = fake()->randomElement($courses);
        
        return [
            'name' => $course[0],
            'code' => $course[1] . '-' . fake()->unique()->numberBetween(100, 999),
            'credits' => $course[2],
            'teaching_unit_id' => TeachingUnit::factory(),
        ];
    }
}

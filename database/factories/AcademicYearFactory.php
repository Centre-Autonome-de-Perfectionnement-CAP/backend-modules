<?php

namespace Database\Factories;

use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Inscription\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 2020;
        $year = $counter++;
        $yearLabel = $year . '-' . ($year + 1);
        
        return [
            'academic_year' => $yearLabel,
            'libelle' => $yearLabel,  // Ajout du champ libelle utilisé par le service
            'year_start' => $year . '-09-01',
            'year_end' => ($year + 1) . '-06-30',
            'submission_start' => $year . '-06-01',
            'submission_end' => $year . '-08-31',
            'is_current' => false,
        ];
    }
}

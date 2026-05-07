<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = date('Y');
        $years = [
            // Année précédente
            [
                'academic_year' => ($currentYear - 1) . '-' . $currentYear,
                'libelle' => 'Année Académique ' . ($currentYear - 1) . '-' . $currentYear,
                'year_start' => Carbon::create($currentYear - 1, 10, 1),
                'year_end' => Carbon::create($currentYear, 6, 30),
                'submission_start' => Carbon::create($currentYear - 1, 8, 1),
                'submission_end' => Carbon::create($currentYear - 1, 9, 30),
                'reclamation_start' => Carbon::create($currentYear, 7, 1),
                'reclamation_end' => Carbon::create($currentYear, 7, 31),
                'is_current' => false,
            ],
            
            // Année en cours
            [
                'academic_year' => $currentYear . '-' . ($currentYear + 1),
                'libelle' => 'Année Académique ' . $currentYear . '-' . ($currentYear + 1),
                'year_start' => Carbon::create($currentYear, 10, 1),
                'year_end' => Carbon::create($currentYear + 1, 6, 30),
                'submission_start' => Carbon::create($currentYear, 8, 1),
                'submission_end' => Carbon::create($currentYear, 9, 30),
                'reclamation_start' => Carbon::create($currentYear + 1, 7, 1),
                'reclamation_end' => Carbon::create($currentYear + 1, 7, 31),
                'is_current' => true,
            ],
            
            // Année suivante
            [
                'academic_year' => ($currentYear + 1) . '-' . ($currentYear + 2),
                'libelle' => 'Année Académique ' . ($currentYear + 1) . '-' . ($currentYear + 2),
                'year_start' => Carbon::create($currentYear + 1, 10, 1),
                'year_end' => Carbon::create($currentYear + 2, 6, 30),
                'submission_start' => Carbon::create($currentYear + 1, 8, 1),
                'submission_end' => Carbon::create($currentYear + 1, 9, 30),
                'reclamation_start' => Carbon::create($currentYear + 2, 7, 1),
                'reclamation_end' => Carbon::create($currentYear + 2, 7, 31),
                'is_current' => false,
            ],
        ];

        foreach ($years as $yearData) {
            AcademicYear::updateOrCreate(
                ['academic_year' => $yearData['academic_year']],
                array_merge($yearData, [
                    'uuid' => Str::uuid()->toString(),
                ])
            );
        }

        $this->command->info('✅ Academic Years créées avec succès!');
    }
}

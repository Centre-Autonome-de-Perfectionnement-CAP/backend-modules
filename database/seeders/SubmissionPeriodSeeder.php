<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Models\ReclamationPeriod;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\AcademicYear;
use Illuminate\Support\Str;

class SubmissionPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        
        if (!$currentYear) {
            $this->command->warn('⚠️  Aucune année académique active. Exécutez AcademicYearSeeder d\'abord.');
            return;
        }

        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->warn('⚠️  Aucun département trouvé. Exécutez DepartmentSeeder d\'abord.');
            return;
        }

        $submissionCount = 0;
        $reclamationCount = 0;

        // Créer des périodes de soumission pour chaque département
        // PÉRIODES ACTIVES MAINTENANT pour les tests
        foreach ($departments as $dept) {
            // Période de soumission (active maintenant pour 3 mois)
            SubmissionPeriod::updateOrCreate(
                [
                    'academic_year_id' => $currentYear->id,
                    'department_id' => $dept->id,
                ],
                [
                    'uuid' => Str::uuid()->toString(),
                    'start_date' => now()->subDays(7), // Commencé il y a 7 jours
                    'end_date' => now()->addMonths(3), // Se termine dans 3 mois
                    'is_active' => true,
                    'description' => "Période de soumission des candidatures pour {$dept->name} - ACTIVE",
                ]
            );
            $submissionCount++;

            // Période de réclamation (sera active plus tard)
            ReclamationPeriod::updateOrCreate(
                [
                    'academic_year_id' => $currentYear->id,
                    'department_id' => $dept->id,
                ],
                [
                    'uuid' => Str::uuid()->toString(),
                    'start_date' => now()->addMonths(4)->startOfMonth(),
                    'end_date' => now()->addMonths(4)->addDays(15),
                    'is_active' => true,
                    'description' => "Période de réclamation pour {$dept->name}",
                ]
            );
            $reclamationCount++;
        }

        // Période générale (sans département spécifique) - ACTIVE MAINTENANT
        SubmissionPeriod::updateOrCreate(
            [
                'academic_year_id' => $currentYear->id,
                'department_id' => null,
            ],
            [
                'uuid' => Str::uuid()->toString(),
                'start_date' => now()->subDays(7), // Commencé il y a 7 jours
                'end_date' => now()->addMonths(3), // Se termine dans 3 mois
                'is_active' => true,
                'description' => "Période générale de soumission des candidatures - ACTIVE",
            ]
        );
        $submissionCount++;

        ReclamationPeriod::updateOrCreate(
            [
                'academic_year_id' => $currentYear->id,
                'department_id' => null,
            ],
            [
                'uuid' => Str::uuid()->toString(),
                'start_date' => now()->addMonths(4)->startOfMonth(),
                'end_date' => now()->addMonths(4)->addDays(15),
                'is_active' => true,
                'description' => "Période générale de réclamation",
            ]
        );
        $reclamationCount++;

        $this->command->info('✅ Périodes créées avec succès!');
        $this->command->info("📅 {$submissionCount} périodes de soumission");
        $this->command->info("📋 {$reclamationCount} périodes de réclamation");
    }
}

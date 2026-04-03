<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Cycle;
use Carbon\Carbon;

class ActiveSubmissionPeriodsSeeder extends Seeder
{
    /**
     * Créer des années académiques avec périodes de soumission actives
     * pour tester les formulaires d'inscription
     */
    public function run(): void
    {
        $this->command->info('🎓 Création des années académiques avec périodes de soumission actives...');
        $this->command->newLine();

        // Année actuelle et suivante
        $currentYear = (int) date('Y');
        $years = [
            [
                'year_start' => $currentYear,
                'year_end' => $currentYear + 1,
                'is_current' => true,
            ],
            [
                'year_start' => $currentYear + 1,
                'year_end' => $currentYear + 2,
                'is_current' => false,
            ],
        ];

        $createdYears = [];
        
        foreach ($years as $yearData) {
            $academicYearString = $yearData['year_start'] . '-' . $yearData['year_end'];
            
            // Créer ou mettre à jour l'année académique
            $academicYear = AcademicYear::updateOrCreate(
                ['academic_year' => $academicYearString],
                [
                    'libelle' => 'Année Académique ' . $academicYearString,
                    'year_start' => Carbon::create($yearData['year_start'], 10, 1),
                    'year_end' => Carbon::create($yearData['year_end'], 6, 30),
                    'submission_start' => Carbon::create($yearData['year_start'], 8, 1),
                    'submission_end' => Carbon::create($yearData['year_start'], 9, 30),
                    'reclamation_start' => Carbon::create($yearData['year_end'], 7, 1),
                    'reclamation_end' => Carbon::create($yearData['year_end'], 7, 31),
                    'is_current' => $yearData['is_current'],
                ]
            );
            
            $createdYears[] = $academicYear;
            $this->command->info("  ✓ Année académique: {$academicYearString}");
        }

        $this->command->newLine();
        $this->command->info('📅 Création des périodes de soumission actives...');

        // Récupérer tous les départements Ingénieur (Prépa + Spécialité)
        $ingenieurCycle = Cycle::where('name', 'LIKE', '%Ingé%')
            ->orWhere('name', 'LIKE', '%Ing%')
            ->first();

        if (!$ingenieurCycle) {
            $this->command->error('❌ Cycle Ingénieur introuvable');
            return;
        }

        $ingenieurDepartments = Department::where('cycle_id', $ingenieurCycle->id)->get();

        // Récupérer aussi les départements Licence et Master pour avoir plus de données
        $licenceCycle = Cycle::where('name', 'LIKE', '%Licence%')->first();
        $masterCycle = Cycle::where('name', 'LIKE', '%Master%')->first();

        $allDepartments = collect();
        $allDepartments = $allDepartments->merge($ingenieurDepartments);
        
        if ($licenceCycle) {
            $allDepartments = $allDepartments->merge(
                Department::where('cycle_id', $licenceCycle->id)->get()
            );
        }
        
        if ($masterCycle) {
            $allDepartments = $allDepartments->merge(
                Department::where('cycle_id', $masterCycle->id)->get()
            );
        }

        $periodsCount = 0;

        foreach ($createdYears as $academicYear) {
            foreach ($allDepartments as $department) {
                // Période de soumission: maintenant jusqu'à dans 3 mois
                $startDate = Carbon::now()->subDays(10); // Commence il y a 10 jours
                $endDate = Carbon::now()->addMonths(3);  // Se termine dans 3 mois

                SubmissionPeriod::updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'department_id' => $department->id,
                    ],
                    [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'is_active' => true,
                    ]
                );

                $periodsCount++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ {$periodsCount} périodes de soumission créées");
        $this->command->newLine();
        
        // Afficher un résumé
        $this->command->info('📊 RÉSUMÉ:');
        $this->command->info("   • Années académiques: " . count($createdYears));
        $this->command->info("   • Départements: " . $allDepartments->count());
        $this->command->info("   • Périodes de soumission: {$periodsCount}");
        $this->command->newLine();
        
        $this->command->info('🎯 DÉPARTEMENTS INGÉNIEUR DISPONIBLES:');
        foreach ($ingenieurDepartments as $dept) {
            $this->command->info("   • {$dept->abbreviation} - {$dept->name}");
        }
        $this->command->newLine();
        
        $this->command->info('✅ Vous pouvez maintenant tester IngenieurSpecialiteForm !');
        $this->command->info('   Dates valides: ' . $startDate->format('Y-m-d') . ' → ' . $endDate->format('Y-m-d'));
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\SubmissionPeriod;
use Carbon\Carbon;

class TestSubmissionPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crée des données de test pour le countdown des inscriptions
     */
    public function run(): void
    {
        $this->command->info('🚀 Création des données de test pour les inscriptions...');

        // 1. Créer ou récupérer le cycle Licence
        $cycleLicence = Cycle::firstOrCreate(
            ['name' => 'Licence'],
            [
                'abbreviation' => 'L',
                'years_count' => 3,
                'is_lmd' => true,
                'type' => 'academic'
            ]
        );
        $this->command->info("✅ Cycle Licence: {$cycleLicence->id}");

        // 2. Créer ou récupérer le département Génie Civil
        $genieCivil = Department::firstOrCreate(
            ['name' => 'Génie Civil', 'cycle_id' => $cycleLicence->id],
            ['abbreviation' => 'GC']
        );
        $this->command->info("✅ Département Génie Civil: {$genieCivil->id}");

        // 3. Créer ou récupérer le département Génie Informatique
        $genieInfo = Department::firstOrCreate(
            ['name' => 'Génie Informatique', 'cycle_id' => $cycleLicence->id],
            ['abbreviation' => 'GI']
        );
        $this->command->info("✅ Département Génie Informatique: {$genieInfo->id}");

        // 4. Créer ou récupérer l'année académique 2025-2026
        $academicYear = AcademicYear::firstOrCreate(
            ['academic_year' => '2025-2026'],
            [
                'year_start' => '2025-09-01',
                'year_end' => '2026-06-30',
                'submission_start' => '2025-10-01',
                'submission_end' => '2026-01-31',
            ]
        );
        $this->command->info("✅ Année académique: {$academicYear->academic_year}");

        // 5. Créer une période de soumission ACTIVE (pour le countdown)
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(7); // Commence il y a 7 jours
        $endDate = $now->copy()->addDays(30); // Finit dans 30 jours

        $submissionPeriodGC = SubmissionPeriod::updateOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'department_id' => $genieCivil->id,
            ],
            [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        );
        $this->command->info("✅ Période Génie Civil: {$startDate->format('Y-m-d')} → {$endDate->format('Y-m-d')}");

        // 6. Créer une période FUTURE (badge "prochainement")
        $futurStart = $now->copy()->addDays(45);
        $futureEnd = $now->copy()->addDays(90);

        $submissionPeriodGI = SubmissionPeriod::updateOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'department_id' => $genieInfo->id,
            ],
            [
                'start_date' => $futurStart->format('Y-m-d'),
                'end_date' => $futureEnd->format('Y-m-d'),
            ]
        );
        $this->command->info("✅ Période Génie Info (future): {$futurStart->format('Y-m-d')} → {$futureEnd->format('Y-m-d')}");

        // 7. Créer un cycle Master avec période fermée
        $cycleMaster = Cycle::firstOrCreate(
            ['name' => 'Master'],
            [
                'abbreviation' => 'M',
                'years_count' => 2,
                'is_lmd' => true,
                'type' => 'academic'
            ]
        );

        $masterInfo = Department::firstOrCreate(
            ['name' => 'Master Informatique', 'cycle_id' => $cycleMaster->id],
            ['abbreviation' => 'MI']
        );

        // Période PASSÉE (badge "inscriptions-fermees")
        $pastStart = $now->copy()->subDays(90);
        $pastEnd = $now->copy()->subDays(10);

        SubmissionPeriod::updateOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'department_id' => $masterInfo->id,
            ],
            [
                'start_date' => $pastStart->format('Y-m-d'),
                'end_date' => $pastEnd->format('Y-m-d'),
            ]
        );
        $this->command->info("✅ Période Master Info (fermée): {$pastStart->format('Y-m-d')} → {$pastEnd->format('Y-m-d')}");

        $this->command->info('');
        $this->command->info('🎉 Données de test créées avec succès !');
        $this->command->info('');
        $this->command->info('📊 Résumé:');
        $this->command->info("   → Génie Civil (Licence): inscriptions-ouvertes jusqu'au {$endDate->format('d/m/Y')}");
        $this->command->info("   → Génie Informatique (Licence): prochainement (à partir du {$futurStart->format('d/m/Y')})");
        $this->command->info("   → Master Informatique: inscriptions-fermees");
        $this->command->info('');
        $this->command->info('🔗 Test les APIs:');
        $this->command->info('   curl http://127.0.0.1:8000/api/next-deadline');
        $this->command->info('   curl http://127.0.0.1:8000/api/filieres');
    }
}

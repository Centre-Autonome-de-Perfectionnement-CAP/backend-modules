<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Models\ReclamationPeriod;
use App\Modules\Inscription\Models\Department;
use Carbon\Carbon;

class AcademicYearCompleteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crée les années académiques avec leurs périodes de soumission et de réclamation
     */
    public function run(): void
    {
        $this->command->info('🎓 Création des années académiques et périodes...');
        $this->command->info('');

        // Définir les années académiques à créer
        $academicYears = [
            [
                'academic_year' => '2023-2024',
                'year_start' => 2023,
                'year_end' => 2024,
                'submission_start' => '2023-09-01',
                'submission_end' => '2023-10-31',
                'is_past' => true,
            ],
            [
                'academic_year' => '2024-2025',
                'year_start' => 2024,
                'year_end' => 2025,
                'submission_start' => '2024-09-01',
                'submission_end' => '2024-10-31',
                'is_current' => true,
            ],
            [
                'academic_year' => '2025-2026',
                'year_start' => 2025,
                'year_end' => 2026,
                'submission_start' => '2025-09-01',
                'submission_end' => '2025-10-31',
                'is_future' => true,
            ],
        ];

        foreach ($academicYears as $yearData) {
            $this->createAcademicYearWithPeriods($yearData);
        }

        $this->command->info('');
        $this->command->info('✅ Création terminée avec succès !');
        $this->command->info('');
        $this->displaySummary();
    }

    /**
     * Crée une année académique avec toutes ses périodes
     */
    private function createAcademicYearWithPeriods(array $data): void
    {
        $this->command->info("📅 Création de l'année académique {$data['academic_year']}...");

        // Créer ou mettre à jour l'année académique
        $academicYear = AcademicYear::updateOrCreate(
            ['academic_year' => $data['academic_year']],
            [
                'year_start' => $data['year_start'],
                'year_end' => $data['year_end'],
                'submission_start' => $data['submission_start'],
                'submission_end' => $data['submission_end'],
            ]
        );

        $this->command->info("   ✓ Année académique créée (ID: {$academicYear->id})");

        // Créer les périodes de soumission
        $submissionCount = $this->createSubmissionPeriods($academicYear, $data);
        $this->command->info("   ✓ $submissionCount périodes de soumission créées");

        // Créer les périodes de réclamation
        $reclamationCount = $this->createReclamationPeriods($academicYear, $data);
        $this->command->info("   ✓ $reclamationCount périodes de réclamation créées");

        $this->command->info('');
    }

    /**
     * Crée les périodes de soumission pour tous les départements
     */
    private function createSubmissionPeriods(AcademicYear $academicYear, array $yearData): int
    {
        $departments = Department::all();
        $count = 0;
        $now = Carbon::now();

        // Calculer les dates de soumission selon le type d'année
        if (isset($yearData['is_current'])) {
            // Année en cours : période active maintenant
            $startDate = $now->copy()->subDays(15);
            $endDate = $now->copy()->addDays(60);
        } elseif (isset($yearData['is_past'])) {
            // Année passée : période terminée
            $startDate = Carbon::parse($yearData['submission_start']);
            $endDate = Carbon::parse($yearData['submission_end']);
        } else {
            // Année future : période à venir
            $startDate = Carbon::parse($yearData['submission_start']);
            $endDate = Carbon::parse($yearData['submission_end']);
        }

        foreach ($departments as $department) {
            // Ajuster légèrement les dates selon le cycle/département
            $deptStartDate = $startDate->copy();
            $deptEndDate = $endDate->copy();

            // Les départements d'ingénierie commencent un peu plus tard
            if (in_array($department->id, range(20, 27))) {
                $deptStartDate->addDays(5);
                $deptEndDate->addDays(5);
            }

            SubmissionPeriod::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'department_id' => $department->id,
                ],
                [
                    'start_date' => $deptStartDate,
                    'end_date' => $deptEndDate,
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Crée les périodes de réclamation
     */
    private function createReclamationPeriods(AcademicYear $academicYear, array $yearData): int
    {
        $count = 0;
        $now = Carbon::now();

        // Configuration des périodes de réclamation
        $reclamationPeriods = [
            [
                'name' => 'Première période',
                'offset_start' => 7,  // 1 semaine après la fin des soumissions
                'offset_end' => 21,   // 3 semaines
            ],
            [
                'name' => 'Deuxième période',
                'offset_start' => 90,  // 3 mois après la fin des soumissions
                'offset_end' => 104,   // 2 semaines
            ],
        ];

        foreach ($reclamationPeriods as $period) {
            if (isset($yearData['is_current'])) {
                // Année en cours
                $baseDate = $now->copy()->addDays(60); // Fin des soumissions
                $startDate = $baseDate->copy()->addDays($period['offset_start']);
                $endDate = $baseDate->copy()->addDays($period['offset_end']);
                $isActive = $now->between($startDate, $endDate);
            } elseif (isset($yearData['is_past'])) {
                // Année passée
                $baseDate = Carbon::parse($yearData['submission_end']);
                $startDate = $baseDate->copy()->addDays($period['offset_start']);
                $endDate = $baseDate->copy()->addDays($period['offset_end']);
                $isActive = false;
            } else {
                // Année future
                $baseDate = Carbon::parse($yearData['submission_end']);
                $startDate = $baseDate->copy()->addDays($period['offset_start']);
                $endDate = $baseDate->copy()->addDays($period['offset_end']);
                $isActive = false;
            }

            ReclamationPeriod::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'start_date' => $startDate,
                ],
                [
                    'end_date' => $endDate,
                    'is_active' => $isActive,
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Affiche un résumé des données créées
     */
    private function displaySummary(): void
    {
        $this->command->info('📊 RÉSUMÉ DES DONNÉES CRÉÉES');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('');

        $academicYears = AcademicYear::with(['submissionPeriod', 'reclamationPeriod'])->get();

        foreach ($academicYears as $year) {
            $submissionCount = $year->submissionPeriod->count();
            $reclamationCount = $year->reclamationPeriod->count();

            $this->command->info("🎓 {$year->academic_year}");
            $this->command->info("   └─ ID: {$year->id}");
            $this->command->info("   └─ Périodes de soumission: $submissionCount");
            $this->command->info("   └─ Périodes de réclamation: $reclamationCount");

            // Afficher les périodes de réclamation actives
            $activeReclamations = $year->reclamationPeriod->where('is_active', true);
            if ($activeReclamations->count() > 0) {
                $this->command->info("   └─ ⚡ Réclamations actives: {$activeReclamations->count()}");
            }

            $this->command->info('');
        }

        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('🔗 TESTEZ LES APIs:');
        $this->command->info('   curl http://127.0.0.1:8000/api/academic-years');
        $this->command->info('   curl http://127.0.0.1:8000/api/filieres');
        $this->command->info('   curl http://127.0.0.1:8000/api/submission-periods');
        $this->command->info('');
    }
}

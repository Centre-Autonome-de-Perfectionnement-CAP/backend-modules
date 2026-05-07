<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompleteMigrationSeeder extends Seeder
{
    /**
     * Seeder MAÎTRE qui exécute TOUTES les migrations dans le bon ordre
     * 
     * Usage: php artisan migrate:fresh --seed --seeder=CompleteMigrationSeeder
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║        MIGRATION COMPLÈTE DE LA BASE DE DONNÉES              ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->info('');

        $startTime = microtime(true);

        // ═══════════════════════════════════════════════════════════════
        // ÉTAPE 1: Entry Diplomas (DOIT être avant les étudiants)
        // ═══════════════════════════════════════════════════════════════
        $this->command->warn('');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('ÉTAPE 1/5: Création des diplômes d\'entrée');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('');
        
        $this->call(\App\Modules\Inscription\Database\Seeders\EntryDiplomaSeeder::class);

        // ═══════════════════════════════════════════════════════════════
        // ÉTAPE 2: Migration des données de l'ancienne base
        // ═══════════════════════════════════════════════════════════════
        $this->command->warn('');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('ÉTAPE 2/5: Migration des données (étudiants + candidats)');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('');
        
        $this->call(MigrationDatabaseSeeder::class);

        // ═══════════════════════════════════════════════════════════════
        // ÉTAPE 3: Départements Préparatoires
        // ═══════════════════════════════════════════════════════════════
        $this->command->warn('');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('ÉTAPE 3/5: Création des départements Préparatoires');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('');
        
        $this->call(DepartmentSeeder::class);

        // ═══════════════════════════════════════════════════════════════
        // ÉTAPE 4: Migration des cursuses (academic_paths réels)
        // ═══════════════════════════════════════════════════════════════
        $this->command->warn('');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('ÉTAPE 4/5: Migration des cursuses (academic_paths)');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('');
        
        $this->call(CursusesMigrationSeeder::class);

        // ═══════════════════════════════════════════════════════════════
        // ÉTAPE 5: Restructuration Ingénieur 4 ans
        // ═══════════════════════════════════════════════════════════════
        $this->command->warn('');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('ÉTAPE 5/5: Restructuration Ingénieur (Prépa + Spécialité)');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('');
        
        $this->call(UpdateIngenieurStudentsSeeder::class);

        // ═══════════════════════════════════════════════════════════════
        // BONUS: Périodes de soumission actives (pour tests)
        // ═══════════════════════════════════════════════════════════════
        $this->command->warn('');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('BONUS: Création des périodes de soumission actives');
        $this->command->warn('═══════════════════════════════════════════════════════════════');
        $this->command->warn('');
        
        $this->call(ActiveSubmissionPeriodsSeeder::class);

        // ═══════════════════════════════════════════════════════════════
        // RÉSUMÉ FINAL
        // ═══════════════════════════════════════════════════════════════
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║              MIGRATION COMPLÈTE TERMINÉE ! ✅                 ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->info('');
        
        $this->displayFinalStats($duration);
    }

    /**
     * Afficher les statistiques finales
     */
    private function displayFinalStats(float $duration): void
    {
        $this->command->info('📊 STATISTIQUES FINALES:');
        $this->command->info('========================');
        
        // Compter les enregistrements
        $stats = [
            'PersonalInformation' => \App\Modules\Inscription\Models\PersonalInformation::count(),
            'Students' => \App\Modules\Inscription\Models\Student::count(),
            'PendingStudents' => \App\Modules\Inscription\Models\PendingStudent::count(),
            'StudentPendingStudent' => \App\Modules\Inscription\Models\StudentPendingStudent::count(),
            'AcademicPaths' => \App\Modules\Inscription\Models\AcademicPath::count(),
            'EntryDiplomas' => \App\Modules\Inscription\Models\EntryDiploma::count(),
            'Departments' => \App\Modules\Inscription\Models\Department::count(),
            'Cycles' => \App\Modules\Inscription\Models\Cycle::count(),
            'AcademicYears' => \App\Modules\Inscription\Models\AcademicYear::count(),
            'SubmissionPeriods' => \App\Modules\Inscription\Models\SubmissionPeriod::count(),
        ];

        foreach ($stats as $model => $count) {
            $this->command->info(sprintf('   • %-25s: %s', $model, number_format($count)));
        }

        $this->command->info('');
        $this->command->info('🎓 DÉPARTEMENTS INGÉNIEUR:');
        $this->command->info('==========================');
        
        $ingCycle = \App\Modules\Inscription\Models\Cycle::where('name', 'LIKE', '%Ing%')->first();
        if ($ingCycle) {
            $depts = \App\Modules\Inscription\Models\Department::where('cycle_id', $ingCycle->id)
                ->orderBy('abbreviation')
                ->get();
            
            foreach ($depts as $dept) {
                $count = \App\Modules\Inscription\Models\PendingStudent::where('department_id', $dept->id)->count();
                $this->command->info(sprintf('   • %-10s: %s étudiants', $dept->abbreviation, number_format($count)));
            }
        }

        $this->command->info('');
        $this->command->info("⏱️  Durée totale: {$duration}s");
        $this->command->info('');
        $this->command->info('✅ La base de données est prête pour les tests !');
        $this->command->info('');
    }
}

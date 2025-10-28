<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Début du seeding...');
        
        // 1. Données de référence (lookup tables)
        $this->command->info('📋 Seeding des données de référence...');
        $this->call([
            RoleSeeder::class,
            EntryDiplomaSeeder::class,
            CycleSeeder::class,
        ]);

        // 2. Structures organisationnelles
        $this->command->info('🏢 Seeding des structures organisationnelles...');
        $this->call([
            DepartmentSeeder::class,
            AcademicYearSeeder::class,
        ]);

        // 3. Utilisateurs et acteurs du système
        $this->command->info('👥 Seeding des utilisateurs...');
        $this->call([
            UserSeeder::class,
            ProfessorSeeder::class,
            StudentSeeder::class,
        ]);

        // 4. Données de test (uniquement en dev/local)
        if (app()->environment('local', 'development')) {
            $this->command->info('🧪 Seeding des données de test...');
            $this->call([
                // AmountSeeder::class, // Nécessite ProgramSeeder avant
                SubmissionPeriodSeeder::class,
                ProgramSeeder::class,
                TeachingUnitSeeder::class,
                CourseElementSeeder::class,
                AmountSeeder::class, // Réactiver après Programs
                PaiementSeeder::class, // Activer si besoin
            ]);
        }

        $this->command->info('✅ Seeding terminé avec succès!');
        $this->command->info('');
        $this->command->warn('📧 COMPTES DE TEST:');
        $this->command->warn('Super Admin: superadmin@cap.edu | password123');
        $this->command->warn('Étudiant: 221234567 | 221234567');
    }
}

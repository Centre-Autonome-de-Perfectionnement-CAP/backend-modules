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
        $this->command>info('📋 Seeding des données de référence...');
        $this->call([
            RoleSeeder::class,
            EntryDiplomaSeeder::class,
            CycleSeeder::class,
        ]);
        $this->command->info('🏢 Seeding des structures organisationnelles...');
        $this->call([
            DepartmentSeeder::class,
            // AcademicYearSeeder::class,
        ]);

        $this->command->info('👥 Seeding des utilisateurs...');
        $this->call([
            UserSeeder::class,
            // ProfessorSeeder::class,
            // StudentSeeder::class,
        ]);

        // if (app()->environment('local', 'development')) {
        //     $this->command->info('🧪 Seeding des données de test...');
        //     $this->call([
        //         SubmissionPeriodSeeder::class,
        //         ProgramSeeder::class,
        //         TeachingUnitSeeder::class,
        //         CourseElementSeeder::class,
        //         AmountSeeder::class, // Réactiver après Programs
        //         PaiementSeeder::class, // Activer si besoin
        //     ]);
        // }
    }
}

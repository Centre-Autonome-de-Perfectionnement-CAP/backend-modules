<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Désactiver les contraintes FK pendant le seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('🌱 Début du seeding...');

        // ── Seeders existants du projet ──────────────────────
        $this->command->info('📋 Données de référence...');
        $this->call([
            RoleSeeder::class,
            EntryDiplomaSeeder::class,
            CycleSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
        ]);

        // ── Seeders importés depuis l'ancienne base ──────────
        $this->command->info('📦 Import des anciennes données...');
        $this->call([
            CyclesSeeder::class,
            GradesSeeder::class,
            EntryDiplomasSeeder::class,
            AcademicYearsSeeder::class,
            DepartmentsSeeder::class,
            AcademicLevelFeesSeeder::class,
            UsersSeeder::class,
            RolesSeeder::class,
            ProfessorsSeeder::class,
            RoleUserSeeder::class,
            SignatairesSeeder::class,
            TeachingUnitsSeeder::class,
            CourseElementsSeeder::class,
            ClassGroupsSeeder::class,
            CourseElementProfessorSeeder::class,
            ProgramsSeeder::class,
            SubmissionPeriodsSeeder::class,
            ReclamationPeriodsSeeder::class,
            PersonalInformationSeeder::class,
            PendingStudentsSeeder::class,
            StudentsSeeder::class,
            StudentPendingStudentSeeder::class,
            AcademicPathsSeeder::class,
            StudentGroupsSeeder::class,
            OldSystemGradesSeeder::class,
            FilesSeeder::class,
            FileActivitiesSeeder::class,
            ImportantInformationsSeeder::class,
            ContactsSeeder::class,
            PaymentsSeeder::class,
        ]);

        // Réactiver les contraintes FK
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Seeding terminé !');
    }
}

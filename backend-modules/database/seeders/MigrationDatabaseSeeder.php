<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MigrationDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║     MIGRATION DE L\'ANCIENNE BASE DE DONNÉES VERS LA NOUVELLE   ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        // Confirmation avant de démarrer
        if (!$this->command->confirm('⚠️  Cette opération va insérer des données. Continuer?', true)) {
            $this->command->warn('Migration annulée par l\'utilisateur.');
            return;
        }

        $this->command->newLine();
        
        // Étape 1: Migrer les données de référence
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('ÉTAPE 1/3: Migration des données de référence');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->call(OldDatabaseMigrationSeeder::class);
        
        $this->command->newLine(2);
        
        // Étape 2: Migrer les étudiants
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('ÉTAPE 2/3: Migration des étudiants');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->call(StudentMigrationSeeder::class);
        
        $this->command->newLine(2);
        
        // Étape 3: Migrer les candidats en attente (avec pièces)
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('ÉTAPE 3/3: Migration des candidats en attente (avec pièces)');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->call(PendingStudentsMigrationSeeder::class);
        
        $this->command->newLine(2);
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║                  MIGRATION TERMINÉE ✅                         ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\SubmissionPeriod;
use App\Modules\Inscription\Models\ReclamationPeriod;
use Carbon\Carbon;

class QuickAcademicYearSeeder extends Seeder
{
    /**
     * Seeder rapide pour créer l'année académique actuelle avec périodes actives
     * Utile pour les tests et le développement
     */
    public function run(): void
    {
        $this->command->info('⚡ Création rapide de l\'année académique actuelle...');
        $this->command->info('');

        $now = Carbon::now();
        $currentYear = $now->year;
        $nextYear = $currentYear + 1;

        // Créer l'année académique actuelle
        $academicYear = AcademicYear::updateOrCreate(
            ['academic_year' => "$currentYear-$nextYear"],
            [
                'year_start' => $currentYear,
                'year_end' => $nextYear,
                'submission_start' => $now->copy()->subDays(30)->format('Y-m-d'),
                'submission_end' => $now->copy()->addDays(60)->format('Y-m-d'),
            ]
        );

        $this->command->info("✓ Année académique: {$academicYear->academic_year} (ID: {$academicYear->id})");

        // Créer UNE période de soumission globale (sans département spécifique)
        $submissionStart = $now->copy()->subDays(15);
        $submissionEnd = $now->copy()->addDays(45);

        SubmissionPeriod::updateOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'department_id' => null, // Période globale
            ],
            [
                'start_date' => $submissionStart,
                'end_date' => $submissionEnd,
            ]
        );

        $this->command->info("✓ Période de soumission: {$submissionStart->format('d/m/Y')} → {$submissionEnd->format('d/m/Y')}");

        // Créer UNE période de réclamation active
        $reclamationStart = $submissionEnd->copy()->addDays(7);
        $reclamationEnd = $reclamationStart->copy()->addDays(14);

        ReclamationPeriod::updateOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'start_date' => $reclamationStart,
            ],
            [
                'end_date' => $reclamationEnd,
                'is_active' => true,
            ]
        );

        $this->command->info("✓ Période de réclamation: {$reclamationStart->format('d/m/Y')} → {$reclamationEnd->format('d/m/Y')}");

        $this->command->info('');
        $this->command->info('🎉 Année académique créée avec succès !');
        $this->command->info('');
        $this->command->info('📊 RÉSUMÉ:');
        $this->command->info("   • Année: {$academicYear->academic_year}");
        $this->command->info("   • Soumissions ouvertes jusqu'au: {$submissionEnd->format('d/m/Y')}");
        $this->command->info("   • Réclamations du: {$reclamationStart->format('d/m/Y')} au {$reclamationEnd->format('d/m/Y')}");
        $this->command->info('');
    }
}

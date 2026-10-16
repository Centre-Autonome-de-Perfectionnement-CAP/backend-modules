<?php

namespace App\Console\Commands;

use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Services\AcademicYearService;
use Illuminate\Console\Command;

class RecalculateWaves extends Command
{
    protected $signature = 'waves:recalculate {--dry-run : Afficher uniquement sans modifier la base}';

    protected $description = 'Recalcule et met à jour le champ initial_wave de tous les dossiers candidats selon les périodes de dépôt réelles';

    public function handle(AcademicYearService $academicYearService): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🔍 Analyse des dossiers candidats...');

        $pendingStudents = PendingStudent::with(['department', 'academicYear'])->get();

        if ($pendingStudents->isEmpty()) {
            $this->info('Aucun dossier trouvé.');
            return 0;
        }

        $this->info("Total dossiers à traiter : {$pendingStudents->count()}");

        $stats = [
            'updated' => 0,
            'already_correct' => 0,
            'by_wave' => [],
        ];

        $bar = $this->output->createProgressBar($pendingStudents->count());
        $bar->start();

        foreach ($pendingStudents as $student) {
            $wave = $academicYearService->resolveWave(
                (int) $student->academic_year_id,
                (int) $student->department_id,
                $student->created_at
            );

            $stats['by_wave'][$wave] = ($stats['by_wave'][$wave] ?? 0) + 1;

            if ($student->initial_wave !== $wave) {
                $stats['updated']++;
                if (!$isDryRun) {
                    $student->initial_wave = $wave;
                    $student->save();
                }
            } else {
                $stats['already_correct']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('📊 Répartition par vague :');
        $rows = [];
        ksort($stats['by_wave']);
        foreach ($stats['by_wave'] as $waveNum => $count) {
            $rows[] = ["Vague {$waveNum}", $count];
        }
        $this->table(['Vague', 'Nombre de candidats'], $rows);

        if ($isDryRun) {
            $this->warn("⚡ Mode DRY-RUN : {$stats['updated']} dossiers auraient été mis à jour.");
        } else {
            $this->info("✅ Succès : {$stats['updated']} dossiers mis à jour, {$stats['already_correct']} déjà corrects.");
        }

        return 0;
    }
}

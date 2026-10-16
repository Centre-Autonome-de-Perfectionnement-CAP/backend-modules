<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DedupeLegacyPrepaPaths extends Command
{
    protected $signature = 'prepa:dedupe-paths
        {sps? : ID de student_pending_student à traiter (optionnel)}
        {--dry-run : Simuler les changements sans les appliquer}';

    protected $description = "Supprime les parcours Prépa dupliqués créés par le rattachement (conserve l'année historique).";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = DB::table('academic_paths')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('study_level', 'like', '%prepa%')
                    ->orWhere('study_level', 'like', '%prépa%');
            })
            ->select('student_pending_student_id')
            ->groupBy('student_pending_student_id')
            ->havingRaw('COUNT(*) > 1');

        if ($sps = $this->argument('sps')) {
            $query->where('student_pending_student_id', (int) $sps);
        }

        $grouped = $query->pluck('student_pending_student_id');

        if ($grouped->isEmpty()) {
            $this->info('Aucun doublon de parcours Prépa détecté.');
            return self::SUCCESS;
        }

        $removed = 0;
        $handled = 0;

        foreach ($grouped as $spsId) {
            // Année académique courante du dossier concerné
            $currentYearId = DB::table('student_pending_student as sps')
                ->join('pending_students as ps', 'ps.id', '=', 'sps.pending_student_id')
                ->where('sps.id', $spsId)
                ->value('ps.academic_year_id');

            $paths = DB::table('academic_paths as ap')
                ->join('academic_years as ay', 'ay.id', '=', 'ap.academic_year_id')
                ->where('ap.student_pending_student_id', $spsId)
                ->whereNull('ap.deleted_at')
                ->where(function ($q) {
                    $q->where('ap.study_level', 'like', '%prepa%')
                        ->orWhere('ap.study_level', 'like', '%prépa%');
                })
                ->orderBy('ay.academic_year')
                ->select('ap.id', 'ap.academic_year_id', 'ap.study_level', 'ay.academic_year')
                ->get();

            if ($paths->count() < 2) {
                continue;
            }

            // Parcours historique = celui qui n'est pas sur l'année courante du dossier.
            $historical = $paths->first(fn ($p) => (int) $p->academic_year_id !== (int) $currentYearId);
            $duplicates = $paths->filter(fn ($p) => (int) $p->academic_year_id === (int) $currentYearId);

            if (! $historical || $duplicates->isEmpty()) {
                $this->warn("sps={$spsId} : plusieurs parcours Prépa mais motif non reconnu, ignoré.");
                continue;
            }

            $handled++;
            foreach ($duplicates as $dup) {
                $this->line(sprintf(
                    'sps=%d : suppression path id=%d (%s, %s) — conservation id=%d (%s)',
                    $spsId,
                    $dup->id,
                    $dup->academic_year,
                    $dup->study_level,
                    $historical->id,
                    $historical->academic_year
                ));

                if (! $dryRun) {
                    DB::table('academic_paths')->where('id', $dup->id)->delete();
                }
                $removed++;
            }
        }

        if ($dryRun) {
            $this->info("[dry-run] {$handled} dossier(s) concerné(s), {$removed} parcours à supprimer.");
        } else {
            $this->info("✓ {$removed} parcours dupliqué(s) supprimé(s) sur {$handled} dossier(s).");
        }

        return self::SUCCESS;
    }
}

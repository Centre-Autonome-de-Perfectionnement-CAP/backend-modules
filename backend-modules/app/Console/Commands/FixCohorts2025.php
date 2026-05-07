<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixCohorts2025 extends Command
{
    protected $signature = 'cohorts:fix-2025 {cohort=1 : Le numéro de cohorte à assigner}';
    
    protected $description = 'Mettre tous les étudiants de 2025-2026 dans une seule cohorte';

    public function handle()
    {
        $targetCohort = $this->argument('cohort');
        $academicYear = '2025-2026';

        $this->info("🔍 Vérification de l'état actuel...");
        
        // État avant
        $before = DB::table('pending_students')
            ->where('academic_year', $academicYear)
            ->select('cohort', DB::raw('COUNT(*) as count'))
            ->groupBy('cohort')
            ->orderBy('cohort')
            ->get();

        $this->table(['Cohorte', 'Nombre d\'étudiants'], 
            $before->map(fn($row) => [$row->cohort, $row->count])->toArray()
        );

        $total = $before->sum('count');
        
        if ($total === 0) {
            $this->error("❌ Aucun étudiant trouvé pour l'année $academicYear");
            return 1;
        }

        // Confirmation
        if (!$this->confirm("⚠️  Voulez-vous mettre les $total étudiants en cohorte $targetCohort ?", true)) {
            $this->info('Opération annulée.');
            return 0;
        }

        // Correction
        $this->info("🔧 Correction en cours...");
        
        $updated = DB::table('pending_students')
            ->where('academic_year', $academicYear)
            ->update(['cohort' => $targetCohort]);

        $this->info("✅ $updated étudiants mis à jour !");

        // État après
        $this->info("\n📊 État après correction :");
        $after = DB::table('pending_students')
            ->where('academic_year', $academicYear)
            ->select('cohort', DB::raw('COUNT(*) as count'))
            ->groupBy('cohort')
            ->orderBy('cohort')
            ->get();

        $this->table(['Cohorte', 'Nombre d\'étudiants'], 
            $after->map(fn($row) => [$row->cohort, $row->count])->toArray()
        );

        $this->info("\n🎉 Terminé !");
        
        return 0;
    }
}

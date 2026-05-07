<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixExistingStudents extends Command
{
    protected $signature = 'students:fix-existing';
    protected $description = 'Fix existing students by adding cohort and role_id';

    public function handle()
    {
        $this->info('Début de la correction des étudiants existants...');
        
        // 1. Récupérer le role_id étudiant
        $studentRoleId = DB::table('roles')->where('name', 'etudiant')->value('id');
        
        if (!$studentRoleId) {
            $this->error('Role "etudiant" non trouvé dans la table roles');
            return 1;
        }
        
        $this->info("Role étudiant trouvé: ID = {$studentRoleId}");
        
        // 2. Récupérer tous les academic_paths avec cohort NULL ou role_id NULL
        $academicPaths = DB::table('academic_paths')
            ->where(function($q) {
                $q->whereNull('cohort')->orWhereNull('role_id');
            })
            ->get();
        
        $this->info("Trouvé {$academicPaths->count()} academic_paths sans cohorte");
        
        $updated = 0;
        
        foreach ($academicPaths as $path) {
            // Récupérer le pending_student via student_pending_student
            $pendingStudent = DB::table('student_pending_student')
                ->join('pending_students', 'student_pending_student.pending_student_id', '=', 'pending_students.id')
                ->where('student_pending_student.id', $path->student_pending_student_id)
                ->select('pending_students.*')
                ->first();
            
            if (!$pendingStudent) {
                continue;
            }
            
            // Calculer la cohorte
            $cohort = $this->determineCohort($pendingStudent);
            
            // Mettre à jour l'academic_path
            DB::table('academic_paths')
                ->where('id', $path->id)
                ->update([
                    'cohort' => $cohort,
                    'role_id' => $studentRoleId,
                ]);
            
            $updated++;
        }
        
        $this->info("✓ {$updated} academic_paths mis à jour avec cohorte et role_id");
        $this->info('Correction terminée avec succès!');
        
        return 0;
    }
    
    private function determineCohort($pendingStudent): string
    {
        $periods = DB::table('submission_periods')
            ->where('academic_year_id', $pendingStudent->academic_year_id)
            ->select('start_date', 'end_date')
            ->distinct()
            ->orderBy('start_date')
            ->get();
        
        if ($periods->isEmpty()) {
            return '1';
        }
        
        $createdAt = \Carbon\Carbon::parse($pendingStudent->created_at);
        $cohortNumber = 1;
        
        foreach ($periods as $index => $period) {
            $startDate = \Carbon\Carbon::parse($period->start_date);
            $endDate = \Carbon\Carbon::parse($period->end_date);
            
            if ($createdAt->between($startDate, $endDate)) {
                $cohortNumber = $index + 1;
                break;
            }
        }
        
        return (string)$cohortNumber;
    }
}

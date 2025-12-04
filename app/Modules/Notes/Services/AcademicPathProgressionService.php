<?php

namespace App\Modules\Notes\Services;

use App\Modules\Inscription\Models\AcademicYear;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicPathProgressionService
{
    /**
     * Créer les academic_paths pour la nouvelle année académique
     * en fonction des décisions de l'année précédente
     * Note: Cette feature ne concerne pas les filières prépa
     */
    public function progressStudents(AcademicYear $newYear): void
    {
        Log::info('=== DÉBUT PROGRESSION AUTOMATIQUE ===', [
            'new_year_id' => $newYear->id,
            'new_year_label' => $newYear->academic_year,
        ]);
        
        DB::transaction(function () use ($newYear) {
            // Récupérer l'année précédente
            $previousYear = AcademicYear::where('year_start', '<', $newYear->year_start)
                ->orderBy('year_start', 'desc')
                ->first();

            if (!$previousYear) {
                Log::warning('Aucune année précédente trouvée, pas de progression automatique');
                return;
            }
            
            Log::info('Année précédente trouvée', [
                'previous_year_id' => $previousYear->id,
                'previous_year_label' => $previousYear->academic_year,
            ]);

            // Récupérer tous les academic_paths de l'année précédente avec décision
            $previousPaths = AcademicPath::with('studentPendingStudent.pendingStudent.department')
                ->where('academic_year_id', $previousYear->id)
                ->whereNotNull('year_decision')
                ->get();
            
            Log::info('Academic paths de l\'année précédente récupérés', [
                'total_paths' => $previousPaths->count(),
            ]);

            $created = 0;
            $skipped = 0;
            $passCount = 0;
            $repeatCount = 0;
            $failCount = 0;
            $prepaSkipped = 0;

            foreach ($previousPaths as $path) {
                // Vérifier si c'est une filière prépa
                $department = $path->studentPendingStudent?->pendingStudent?->department;
                $isPrepa = $department && stripos($department->name, 'prepa') !== false;
                
                if ($isPrepa) {
                    Log::debug('Filière prépa ignorée', [
                        'student_pending_student_id' => $path->student_pending_student_id,
                        'department_name' => $department->name,
                    ]);
                    $prepaSkipped++;
                    continue;
                }

                // Déterminer si on crée une nouvelle ligne
                if ($path->year_decision === 'pass') {
                    $passCount++;
                    
                    // Étudiant passe à l'année suivante
                    $newStudyLevel = $path->study_level + 1;
                    $newCohort = ($path->cohort && $path->cohort != 1) ? 1 : $path->cohort;

                    $newPath = AcademicPath::create([
                        'student_pending_student_id' => $path->student_pending_student_id,
                        'academic_year_id' => $newYear->id,
                        'study_level' => $newStudyLevel,
                        'role_id' => $path->role_id,
                        'financial_status' => $path->financial_status,
                        'cohort' => $newCohort,
                        'year_decision' => null,
                        'deliberation_date' => null,
                    ]);
                    
                    Log::debug('Cursus créé pour étudiant PASS', [
                        'new_path_id' => $newPath->id,
                        'student_pending_student_id' => $path->student_pending_student_id,
                        'old_level' => $path->study_level,
                        'new_level' => $newStudyLevel,
                        'old_cohort' => $path->cohort,
                        'new_cohort' => $newCohort,
                    ]);
                    
                    $created++;
                } elseif ($path->year_decision === 'repeat') {
                    $repeatCount++;
                    // Étudiant redouble
                    $newPath = AcademicPath::create([
                        'student_pending_student_id' => $path->student_pending_student_id,
                        'academic_year_id' => $newYear->id,
                        'study_level' => $path->study_level,
                        'role_id' => $path->role_id,
                        'financial_status' => $path->financial_status,
                        'cohort' => $path->cohort,
                        'year_decision' => null,
                        'deliberation_date' => null,
                    ]);
                    
                    Log::debug('Cursus créé pour étudiant REPEAT', [
                        'new_path_id' => $newPath->id,
                        'student_pending_student_id' => $path->student_pending_student_id,
                        'level' => $path->study_level,
                    ]);
                    
                    $created++;
                } elseif ($path->year_decision === 'fail') {
                    $failCount++;
                    // Étudiant exclu, on ne crée rien
                    Log::debug('Étudiant exclu, pas de cursus créé', [
                        'student_pending_student_id' => $path->student_pending_student_id,
                    ]);
                    $skipped++;
                }
            }

            Log::info('=== FIN PROGRESSION AUTOMATIQUE ===', [
                'new_year_id' => $newYear->id,
                'previous_year_id' => $previousYear->id,
                'total_paths_analyzed' => $previousPaths->count(),
                'pass_count' => $passCount,
                'repeat_count' => $repeatCount,
                'fail_count' => $failCount,
                'prepa_skipped' => $prepaSkipped,
                'total_created' => $created,
                'total_skipped' => $skipped,
            ]);
        });
    }
}

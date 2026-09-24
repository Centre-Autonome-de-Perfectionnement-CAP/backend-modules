<?php

namespace App\Console\Commands;

use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Services\ClassGroupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Range dans le groupe de leur classe les étudiants approuvés qui n'en ont aucun.
 *
 * Ne traite que les classes (année, filière, niveau) ne comportant qu'un seul nom
 * de groupe, comme ClassGroupService::assignStudentToSingleGroup() à l'approbation.
 * Idempotent : peut être relancé sans risque.
 */
class SyncStudentGroups extends Command
{
    protected $signature = 'inscription:sync-groups
                            {--year= : Limiter à une année académique (id)}
                            {--dry-run : Afficher ce qui serait fait sans rien écrire}';

    protected $description = 'Ajoute les étudiants approuvés sans groupe au groupe unique de leur classe';

    public function handle(ClassGroupService $groups): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $classes = ClassGroup::query()
            ->select('academic_year_id', 'department_id', 'study_level')
            ->when($this->option('year'), fn ($q, $year) => $q->where('academic_year_id', $year))
            ->distinct()
            ->get();

        $assigned = 0;

        foreach ($classes as $class) {
            $names = ClassGroup::where('academic_year_id', $class->academic_year_id)
                ->where('department_id', $class->department_id)
                ->where('study_level', $class->study_level)
                ->pluck('group_name')
                ->unique();

            if ($names->count() !== 1) {
                $this->line("Ignoré (plusieurs groupes) : année {$class->academic_year_id}, filière {$class->department_id}, niveau {$class->study_level}");
                continue;
            }

            $studentIds = DB::table('pending_students as p')
                ->join('student_pending_student as l', 'l.pending_student_id', '=', 'p.id')
                ->where('p.status', 'approved')
                ->where('p.academic_year_id', $class->academic_year_id)
                ->where('p.department_id', $class->department_id)
                ->where('p.level', $class->study_level)
                ->whereNotExists(function ($q) use ($class) {
                    $q->select(DB::raw(1))
                        ->from('student_groups as sg')
                        ->join('class_groups as cg', 'cg.id', '=', 'sg.class_group_id')
                        ->whereColumn('sg.student_id', 'l.student_id')
                        ->where('cg.academic_year_id', $class->academic_year_id)
                        ->where('cg.department_id', $class->department_id)
                        ->where('cg.study_level', $class->study_level);
                })
                ->pluck('l.student_id')
                ->unique();

            foreach ($studentIds as $studentId) {
                $this->line(($dryRun ? '[dry-run] ' : '')."étudiant {$studentId} → groupe \"{$names->first()}\" (année {$class->academic_year_id}, filière {$class->department_id}, niveau {$class->study_level})");

                if (!$dryRun) {
                    $groups->assignStudentToSingleGroup(
                        (int) $studentId,
                        $class->academic_year_id,
                        $class->department_id,
                        $class->study_level
                    );
                }
                $assigned++;
            }
        }

        $this->info(($dryRun ? 'À affecter : ' : 'Affectés : ').$assigned);

        return self::SUCCESS;
    }
}

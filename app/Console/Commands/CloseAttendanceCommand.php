<?php

namespace App\Modules\Attendance\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Commande : php artisan attendance:close
 *
 * Rôle : Après la fin de chaque cours, marquer automatiquement
 *        comme ABSENTS tous les étudiants qui n'ont pas pointé.
 *
 * À planifier dans app/Console/Kernel.php :
 *   $schedule->command('attendance:close')->everyFifteenMinutes();
 *   // ou
 *   $schedule->command('attendance:close')->dailyAt('22:15');
 */
class CloseAttendanceCommand extends Command
{
    protected $signature   = 'attendance:close {--date= : Date à traiter (YYYY-MM-DD, défaut=aujourd\'hui)} {--force : Forcer même si le cours n\'est pas terminé}';
    protected $description = 'Clôture les cours terminés et marque absents les étudiants non pointés';

    public function handle(): int
    {
        $date = $this->option('date') ?? Carbon::today()->format('Y-m-d');
        $force = $this->option('force');
        $now   = Carbon::now();

        $this->info("📋 Clôture des présences pour le {$date}...");

        // 1. Récupérer tous les cours de la journée qui sont terminés
        $dayOfWeek = strtolower(Carbon::parse($date)->englishDayOfWeek);

        $cours = DB::table('emploi_du_temps')
            ->join('course_elements', 'emploi_du_temps.course_element_id', '=', 'course_elements.id')
            ->join('departments',     'emploi_du_temps.department_id',     '=', 'departments.id')
            ->where('emploi_du_temps.is_cancelled', 0)
            ->where('emploi_du_temps.is_active',    1)
            ->where('emploi_du_temps.day_of_week',  $dayOfWeek)
            ->where(function ($q) use ($date) {
                $q->whereNull('emploi_du_temps.recurrence_start_date')
                  ->orWhere('emploi_du_temps.recurrence_start_date', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('emploi_du_temps.recurrence_end_date')
                  ->orWhere('emploi_du_temps.recurrence_end_date', '>=', $date);
            })
            ->select(
                'emploi_du_temps.id as edt_id',
                'emploi_du_temps.course_element_id',
                'emploi_du_temps.department_id',
                'emploi_du_temps.start_time',
                'emploi_du_temps.end_time',
                'emploi_du_temps.room_id',
                'course_elements.name as matiere',
                'departments.abbreviation as filiere',
            )
            ->get();

        if ($cours->isEmpty()) {
            $this->warn("Aucun cours trouvé pour le {$date} ({$dayOfWeek}).");
            return Command::SUCCESS;
        }

        $totalAbsents = 0;
        $totalCours   = 0;

        foreach ($cours as $c) {
            $courseEnd = Carbon::parse($date . ' ' . $c->end_time);

            // Vérifier que le cours est bien terminé (+ 10 min de grâce)
            if (!$force && $now->lt($courseEnd->addMinutes(10))) {
                $this->line("  ⏳ {$c->matiere} ({$c->filiere}) — pas encore terminé, ignoré");
                continue;
            }

            $totalCours++;
            $absents = $this->closeOneCourse($c, $date);
            $totalAbsents += $absents;

            $this->info("  ✓ {$c->matiere} ({$c->filiere}) — {$absents} absent(s) marqué(s)");
        }

        $this->info('');
        $this->info("✅ Clôture terminée : {$totalCours} cours traité(s), {$totalAbsents} absent(s) total.");
        return Command::SUCCESS;
    }

    /**
     * Clôturer un cours : marquer absents tous les étudiants sans pointage
     */
    private function closeOneCourse(object $cours, string $date): int
    {
        // Récupérer tous les étudiants de la filière concernée
        $year = DB::table('academic_years')
            ->where('academic_year', 'like', '%2025%')
            ->orderByDesc('id')
            ->value('id');

        $students = DB::table('students')
            ->where('filiere_id',       $cours->department_id)
            ->where('academic_year_id', $year)
            ->pluck('id');

        $absentsCount = 0;
        $now_ts = now();

        foreach ($students as $studentId) {
            // Vérifier s'il a déjà un enregistrement pour ce cours ce jour
            $existing = DB::table('attendances')
                ->where('student_id',        $studentId)
                ->where('course_element_id', $cours->course_element_id)
                ->whereDate('date',           $date)
                ->first();

            if (!$existing) {
                // Aucun enregistrement → ABSENT
                DB::table('attendances')->insert([
                    'student_id'        => $studentId,
                    'course_element_id' => $cours->course_element_id,
                    'room_id'           => $cours->room_id,
                    'date'              => $date,
                    'status'            => 'absent',
                    'on_time'           => 0,
                    'late_type'         => null,
                    'first_entry'       => null,
                    'last_exit'         => null,
                    'total_minutes'     => 0,
                    'created_at'        => $now_ts,
                    'updated_at'        => $now_ts,
                ]);
                $absentsCount++;
            } elseif ($existing->status !== 'present') {
                // Enregistrement existe mais pas présent → s'assurer qu'il est bien absent
                DB::table('attendances')
                    ->where('id', $existing->id)
                    ->update([
                        'status'     => 'absent',
                        'updated_at' => $now_ts,
                    ]);
            }
        }

        return $absentsCount;
    }
}

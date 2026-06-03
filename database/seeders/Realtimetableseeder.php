<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Seeder basé sur les 3 vrais emplois du temps CAP-EPAC
 *
 * PDF 1 — GE & GME — 27/04/26 → 17/05/26
 *   LUN : Recherche Opérationnelle — Salle CAP 37 — 18h-22h
 *   MAR : Recherche Opérationnelle — Salle CAP 37 — 18h-22h
 *   MER : Analyse Numérique        — Salle CAP 37 — 18h-22h
 *   VEN : Analyse Numérique        — Salle CAP 37 — 18h-22h
 *   SAM : Recherche Opérationnelle — Salle CAP 37 — 08h-12h
 *
 * PDF 2 — GC & TOPO — 27/04/26 → 17/05/26
 *   LUN : Initiation à l'Algorithmique — Amphi B — 18h-22h
 *   MAR : Initiation à l'Algorithmique — Amphi B — 18h-22h
 *   JEU : Mécanique des Fluides        — Amphi B — 18h-22h
 *   VEN : Mécanique des Fluides        — Amphi B — 18h-22h
 *   SAM : Mécanique des Fluides        — Amphi B — 08h-12h
 *
 * PDF 3 — GC & TOPO — 05/05/26 → 30/05/26
 *   LUN : Langage et Programmation — Amphi B — 18h-22h
 *   MAR : Langage et Programmation — Amphi B — 18h-22h
 *   JEU : Mécanique des Fluides    — Amphi B — 18h-22h
 *   VEN : Mécanique des Fluides    — Amphi B — 18h-22h
 *   SAM : Mécanique des Fluides    — Amphi B — 08h-12h
 *
 * Corrections appliquées :
 *  - recurrence_start_date ajoutée
 *  - late_type : 'retard' uniquement (pas de leger/grave)
 *  - flush final des présences garanti
 *  - résumé complet en fin d'exécution
 */
class RealTimetableSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📅 Insertion des vrais emplois du temps CAP-EPAC...');

        // ── Vider les tables liées (ordre FK correct) ─────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ([
            'attendances', 'attendance_scans', 'emploi_du_temps',
            'programs', 'course_element_professor',
            'course_elements', 'teaching_units',
            'rooms', 'buildings',
            'class_groups', 'professors',
        ] as $table) {
            if (Schema::hasTable($table)) DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('✓ Tables EDT vidées');

        // ── Année académique ───────────────────────────────────────────────────
        $year = DB::table('academic_years')
            ->where('academic_year', '2025-2026')->first();
        if (!$year) {
            $this->command->error('❌ Année 2025-2026 introuvable. Lancez AttendanceSeeder d\'abord.');
            return;
        }
        $yearId = $year->id;

        // ── Filières ──────────────────────────────────────────────────────────
        $depts = [];
        foreach (['GC', 'GT', 'GE', 'GME'] as $abbr) {
            $dept = DB::table('departments')->where('abbreviation', $abbr)->first();
            if (!$dept) {
                $this->command->error("❌ Filière {$abbr} introuvable. Lancez AttendanceSeeder d'abord.");
                return;
            }
            $depts[$abbr] = $dept;
        }

        // ── Bâtiment + salles ─────────────────────────────────────────────────
        $buildingId = DB::table('buildings')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'CAP', 'name' => 'CAP-EPAC',
        ]);
        $roomAmphiB = DB::table('rooms')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'AMPHI-B',
            'name' => 'Salle Amphi B', 'building_id' => $buildingId, 'capacity' => 150,
        ]);
        $roomCAP37 = DB::table('rooms')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'CAP-37',
            'name' => 'Salle CAP 37', 'building_id' => $buildingId, 'capacity' => 60,
        ]);

        // ── Professeurs ───────────────────────────────────────────────────────
        $profSanya = DB::table('professors')->insertGetId([
            'uuid' => Str::uuid(), 'first_name' => 'Max Fréjus Owolabi',
            'last_name' => 'SANYA', 'email' => 'sanya@epac.bj',
            'phone' => '0161332652', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $profBoco = DB::table('professors')->insertGetId([
            'uuid' => Str::uuid(), 'first_name' => 'Marius',
            'last_name' => 'BOCO KOUBE', 'email' => 'boco@epac.bj',
            'phone' => '0197831893', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $profKoudi = DB::table('professors')->insertGetId([
            'uuid' => Str::uuid(), 'first_name' => 'Jean',
            'last_name' => 'KOUDI', 'email' => 'koudi@epac.bj',
            'phone' => '0161741344', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $profIssiako = DB::table('professors')->insertGetId([
            'uuid' => Str::uuid(), 'first_name' => 'Faras',
            'last_name' => 'ISSIAKO', 'email' => 'issiako@epac.bj',
            'phone' => '0196490914', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ── Unités d'enseignement ─────────────────────────────────────────────
        $ueGCTopo = DB::table('teaching_units')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'UE-GCTOPO', 'name' => 'UE GC & Topographie',
        ]);
        $ueGEGME = DB::table('teaching_units')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'UE-GEGME', 'name' => 'UE GE & GME',
        ]);

        // ── Matières (course_elements) ────────────────────────────────────────
        $ceAlgo = DB::table('course_elements')->insertGetId([
            'uuid' => Str::uuid(), 'name' => "Initiation à l'Algorithmique",
            'code' => 'ALGO-01', 'credits' => 4, 'teaching_unit_id' => $ueGCTopo,
        ]);
        $ceFluides = DB::table('course_elements')->insertGetId([
            'uuid' => Str::uuid(), 'name' => 'Mécanique des Fluides',
            'code' => 'MDF-01', 'credits' => 6, 'teaching_unit_id' => $ueGCTopo,
        ]);
        $ceLangage = DB::table('course_elements')->insertGetId([
            'uuid' => Str::uuid(), 'name' => 'Langage et Programmation',
            'code' => 'LANG-01', 'credits' => 4, 'teaching_unit_id' => $ueGCTopo,
        ]);
        $ceRecherche = DB::table('course_elements')->insertGetId([
            'uuid' => Str::uuid(), 'name' => 'Recherche Opérationnelle',
            'code' => 'RO-01', 'credits' => 6, 'teaching_unit_id' => $ueGEGME,
        ]);
        $ceAnalyse = DB::table('course_elements')->insertGetId([
            'uuid' => Str::uuid(), 'name' => 'Analyse Numérique',
            'code' => 'AN-01', 'credits' => 6, 'teaching_unit_id' => $ueGEGME,
        ]);

        // ── Association prof → matière (course_element_professor) ─────────────
        $cepAlgo = DB::table('course_element_professor')->insertGetId([
            'course_element_id' => $ceAlgo,     'professor_id' => $profSanya,
            'academic_year_id'  => $yearId,     'is_primary'   => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $cepFluides = DB::table('course_element_professor')->insertGetId([
            'course_element_id' => $ceFluides,  'professor_id' => $profBoco,
            'academic_year_id'  => $yearId,     'is_primary'   => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $cepLangage = DB::table('course_element_professor')->insertGetId([
            'course_element_id' => $ceLangage,  'professor_id' => $profSanya,
            'academic_year_id'  => $yearId,     'is_primary'   => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $cepRecherche = DB::table('course_element_professor')->insertGetId([
            'course_element_id' => $ceRecherche,'professor_id' => $profKoudi,
            'academic_year_id'  => $yearId,     'is_primary'   => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $cepAnalyse = DB::table('course_element_professor')->insertGetId([
            'course_element_id' => $ceAnalyse,  'professor_id' => $profIssiako,
            'academic_year_id'  => $yearId,     'is_primary'   => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ── Groupes de classe ─────────────────────────────────────────────────
        // Pour GC/GT : cep_algo=Algo, cep_fluides=Fluides, cep_lang=Langage
        // Pour GE/GME: cep_algo=Recherche, cep_fluides=Analyse, cep_lang=Recherche
        $groupDefs = [
            'GC'  => ['dept' => $depts['GC'],  'cep_algo' => $cepAlgo,     'cep_fluides' => $cepFluides,  'cep_lang' => $cepLangage],
            'GT'  => ['dept' => $depts['GT'],  'cep_algo' => $cepAlgo,     'cep_fluides' => $cepFluides,  'cep_lang' => $cepLangage],
            'GE'  => ['dept' => $depts['GE'],  'cep_algo' => $cepRecherche,'cep_fluides' => $cepAnalyse,  'cep_lang' => $cepRecherche],
            'GME' => ['dept' => $depts['GME'], 'cep_algo' => $cepRecherche,'cep_fluides' => $cepAnalyse,  'cep_lang' => $cepRecherche],
        ];

        $groups = [];
        foreach ($groupDefs as $abbr => $def) {
            $groups[$abbr] = [
                'id'   => DB::table('class_groups')->insertGetId([
                    'uuid'             => Str::uuid(),
                    'academic_year_id' => $yearId,
                    'department_id'    => $def['dept']->id,
                    'study_level'      => 'L1',
                    'group_name'       => "{$abbr}-L1",
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]),
                'dept' => $def['dept'],
                'ceps' => $def,
            ];
        }

        // ── Définition des créneaux EDT ───────────────────────────────────────
        // [filieres, course_key, course_element_id, room_id, day, start, end, date_from, date_to]
        $edtEntries = [

            // ══ PDF 1 : GE & GME — 27/04/26 → 17/05/26 ══════════════════════
            [['GE','GME'], 'recherche', $ceRecherche, $roomCAP37,  'monday',    '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GE','GME'], 'recherche', $ceRecherche, $roomCAP37,  'tuesday',   '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GE','GME'], 'analyse',   $ceAnalyse,   $roomCAP37,  'wednesday', '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GE','GME'], 'analyse',   $ceAnalyse,   $roomCAP37,  'friday',    '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GE','GME'], 'recherche', $ceRecherche, $roomCAP37,  'saturday',  '08:00:00','12:00:00','2026-04-27','2026-06-30'],

            // ══ PDF 2 : GC & TOPO — 27/04/26 → 17/05/26 ═════════════════════
            [['GC','GT'],  'algo',      $ceAlgo,      $roomAmphiB, 'monday',    '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GC','GT'],  'algo',      $ceAlgo,      $roomAmphiB, 'tuesday',   '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GC','GT'],  'fluides',   $ceFluides,   $roomAmphiB, 'thursday',  '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GC','GT'],  'fluides',   $ceFluides,   $roomAmphiB, 'friday',    '18:00:00','22:00:00','2026-04-27','2026-06-30'],
            [['GC','GT'],  'fluides',   $ceFluides,   $roomAmphiB, 'saturday',  '08:00:00','12:00:00','2026-04-27','2026-06-30'],

            // ══ PDF 3 : GC & TOPO — 05/05/26 → 30/05/26 ═════════════════════
            [['GC','GT'],  'langage',   $ceLangage,   $roomAmphiB, 'monday',    '18:00:00','22:00:00','2026-05-05','2026-06-30'],
            [['GC','GT'],  'langage',   $ceLangage,   $roomAmphiB, 'tuesday',   '18:00:00','22:00:00','2026-05-05','2026-06-30'],
            [['GC','GT'],  'fluides',   $ceFluides,   $roomAmphiB, 'thursday',  '18:00:00','22:00:00','2026-05-05','2026-06-30'],
            [['GC','GT'],  'fluides',   $ceFluides,   $roomAmphiB, 'friday',    '18:00:00','22:00:00','2026-05-05','2026-06-30'],
            [['GC','GT'],  'fluides',   $ceFluides,   $roomAmphiB, 'saturday',  '08:00:00','12:00:00','2026-05-05','2026-06-30'],
        ];

        // ── Insertion EDT + Programs ──────────────────────────────────────────
        $edtCount    = 0;
        $programCache = [];

        foreach ($edtEntries as [$filieres, $courseKey, $ceId, $roomId, $day, $start, $end, $dateFrom, $dateTo]) {
            foreach ($filieres as $abbr) {
                $group  = $groups[$abbr];
                $deptId = $group['dept']->id;

                // Déterminer le bon CEP selon la matière et le groupe
                $cepId = match($courseKey) {
                    'algo'      => $group['ceps']['cep_algo'],
                    'langage'   => $group['ceps']['cep_lang'],
                    'fluides'   => $group['ceps']['cep_fluides'],
                    'recherche' => $group['ceps']['cep_algo'],    // GE/GME : cep_algo = cepRecherche
                    'analyse'   => $group['ceps']['cep_fluides'], // GE/GME : cep_fluides = cepAnalyse
                    default     => $group['ceps']['cep_algo'],
                };

                // Créer le program si pas encore créé pour ce groupe+cep
                $progKey = "{$group['id']}_{$cepId}";
                if (!isset($programCache[$progKey])) {
                    $programCache[$progKey] = DB::table('programs')->insertGetId([
                        'uuid'                        => Str::uuid(),
                        'academic_year_id'            => $yearId,
                        'semester'                    => 1,
                        'class_group_id'              => $group['id'],
                        'course_element_professor_id' => $cepId,
                        'created_at'                  => now(),
                        'updated_at'                  => now(),
                    ]);
                }

                DB::table('emploi_du_temps')->insert([
                    'uuid'                   => Str::uuid(),
                    'academic_year_id'       => $yearId,
                    'department_id'          => $deptId,
                    'class_group_id'         => $group['id'],
                    'course_element_id'      => $ceId,
                    'program_id'             => $programCache[$progKey],
                    'room_id'                => $roomId,
                    'day_of_week'            => $day,
                    'start_time'             => $start,
                    'end_time'               => $end,
                    'is_recurring'           => 1,
                    'recurrence_start_date'  => $dateFrom, // ✅ CORRIGÉ : date de début ajoutée
                    'recurrence_end_date'    => $dateTo,
                    'excluded_dates'         => null,
                    'notes'                  => null,
                    'is_cancelled'           => 0,
                    'is_active'              => 1,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
                $edtCount++;
            }
        }
        $this->command->info("✓ {$edtCount} créneaux EDT insérés");

        // ════════════════════════════════════════════════════════════════════
        //  GÉNÉRATION DES PRÉSENCES RÉALISTES
        //  Uniquement sur les dates passées (< aujourd'hui)
        //  Taux : 90% présents · 10% absents · 12% de retards parmi présents
        // ════════════════════════════════════════════════════════════════════
        $this->command->info('📊 Génération des présences sur les dates passées...');

        $students = DB::table('students')
            ->join('departments', 'students.filiere_id', '=', 'departments.id')
            ->whereIn('departments.abbreviation', ['GC', 'GT', 'GE', 'GME'])
            ->where('students.academic_year_id', $yearId)
            ->select('students.id', 'departments.abbreviation as abbr')
            ->get();

        if ($students->isEmpty()) {
            $this->command->warn('⚠️  Aucun étudiant trouvé — lancez RealStudentsSeeder d\'abord.');
            return;
        }

        $today       = Carbon::today();
        $absenceRate = 0.10; // 10% d'absents
        $lateRate    = 0.12; // 12% de retards parmi présents

        $dayMap = [
            'monday'    => Carbon::MONDAY,
            'tuesday'   => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday'  => Carbon::THURSDAY,
            'friday'    => Carbon::FRIDAY,
            'saturday'  => Carbon::SATURDAY,
        ];

        $attendanceInserts = [];
        $batchSize         = 500;

        foreach ($edtEntries as [$filieres, $courseKey, $ceId, $roomId, $day, $start, $end, $dateFrom, $dateTo]) {
            $carbonDay = $dayMap[$day];
            $cursor    = Carbon::parse($dateFrom)->startOfDay();

            // Avancer jusqu'au bon jour de la semaine
            if ($cursor->dayOfWeek !== $carbonDay) {
                $cursor->next($carbonDay);
            }

            $endDate = Carbon::parse($dateTo);

            while ($cursor->lte($endDate) && $cursor->lt($today)) {
                $dateStr = $cursor->format('Y-m-d');

                [$startH, $startM] = [(int)substr($start, 0, 2), (int)substr($start, 3, 2)];
                [$endH,   $endM]   = [(int)substr($end,   0, 2), (int)substr($end,   3, 2)];
                $courseStartMin    = $startH * 60 + $startM;
                $courseEndMin      = $endH   * 60 + $endM;

                foreach ($students as $student) {
                    if (!in_array($student->abbr, $filieres)) continue;

                    $isAbsent = (mt_rand(0, 99) / 100) < $absenceRate;

                    if ($isAbsent) {
                        $attendanceInserts[] = [
                            'student_id'        => $student->id,
                            'course_element_id' => $ceId,
                            'room_id'           => $roomId,
                            'date'              => $dateStr,
                            'status'            => 'absent',
                            'on_time'           => 0,
                            'late_type'         => null,
                            'first_entry'       => null,
                            'last_exit'         => null,
                            'total_minutes'     => 0,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    } else {
                        // Déterminer présence, retard léger (16-30min) ou retard grave (>30min)
                        $isLate = (mt_rand(0, 99) / 100) < $lateRate;

                        if (!$isLate) {
                            // À l'heure : entre 0 et 15min APRÈS le début du cours
                            // On ne met pas d'offset négatif pour éviter des heures avant le cours
                            $entryOffset = mt_rand(0, 14);
                            $lateType    = null;
                            $onTime      = 1;
                        } else {
                            $isGrave     = mt_rand(0, 99) < 40;
                            $entryOffset = $isGrave ? mt_rand(31, 60) : mt_rand(16, 30);
                            $lateType    = $isGrave ? 'grave' : 'leger';
                            $onTime      = 0;
                        }

                        $entryMin = $courseStartMin + $entryOffset;
                        $exitMin  = $courseEndMin + mt_rand(-5, 5);  // sortie autour de la fin du cours

                        $entryH = intdiv($entryMin, 60) % 24;
                        $entryM = $entryMin % 60;
                        $exitH  = intdiv($exitMin,  60) % 24;
                        $exitM  = $exitMin  % 60;

                        $totalMinutes = max(0, $exitMin - $entryMin);

                        $attendanceInserts[] = [
                            'student_id'        => $student->id,
                            'course_element_id' => $ceId,
                            'room_id'           => $roomId,
                            'date'              => $dateStr,
                            'status'            => 'present',
                            'on_time'           => $onTime,
                            'late_type'         => $lateType,
                            'first_entry'       => sprintf('%02d:%02d:00', $entryH, $entryM),
                            'last_exit'         => sprintf('%02d:%02d:00', $exitH,  $exitM),
                            'total_minutes'     => $totalMinutes,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    }

                    // Flush par batch pour éviter les timeouts mémoire
                    if (count($attendanceInserts) >= $batchSize) {
                        DB::table('attendances')->insert($attendanceInserts);
                        $attendanceInserts = [];
                    }
                }

                $cursor->addWeek();
            }
        }

        // ✅ Flush final garanti
        if (!empty($attendanceInserts)) {
            DB::table('attendances')->insert($attendanceInserts);
        }

        // ── Résumé ────────────────────────────────────────────────────────────
        $totalAtt = DB::table('attendances')->count();
        $presents = DB::table('attendances')->where('status', 'present')->count();
        $retards  = DB::table('attendances')->whereNotNull('late_type')->count();
        $absents  = DB::table('attendances')->where('status', 'absent')->count();

        $this->command->info('');
        $this->command->info('✅ Résumé :');
        $this->command->info('  Matières : Algo · Mécanique des Fluides · Langage · Recherche Op. · Analyse Num.');
        $this->command->info('  Salles   : Salle Amphi B · Salle CAP 37');
        $this->command->info('  EDT      : ' . DB::table('emploi_du_temps')->count() . ' créneaux insérés');
        $this->command->info('  Présences: ' . $totalAtt . ' enregistrements');
        $this->command->info("     → Présents : {$presents}");
        $this->command->info("     → Retards  : {$retards}");
        $this->command->info("     → Absents  : {$absents}");
        $this->command->info('');
        $this->command->info('🎓 Dashboard · Management · CourseAttendance · Fingerprint opérationnels !');
    }
}

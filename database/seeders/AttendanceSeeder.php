<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Amorçage EPAC — présences réparties sur toute l\'année...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tables = [
            'attendances', 'emploi_du_temps', 'programs',
            'course_element_professor', 'professors', 'class_groups',
            'students', 'course_elements', 'teaching_units',
            'departments', 'academic_years', 'rooms', 'buildings',
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ── 1. ANNÉES ACADÉMIQUES ─────────────────────────────────────────────
        $yearIds = [];
        foreach (['2023-2024', '2024-2025', '2025-2026'] as $label) {
            [$s, $e] = explode('-', $label);
            $yearIds[$label] = DB::table('academic_years')->insertGetId([
                'uuid'          => Str::uuid(),
                'academic_year' => $label,
                'year_start'    => "{$s}-10-01",
                'year_end'      => "{$e}-06-30",
                'is_current'    => ($label === '2025-2026' ? 1 : 0),
                'created_at'    => now(), 'updated_at' => now(),
            ]);
        }

        $profId = DB::table('professors')->insertGetId([
            'uuid' => Str::uuid(), 'first_name' => 'Admin', 'last_name' => 'EPAC',
            'email' => 'admin@epac.bj', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $buildingId = DB::table('buildings')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'A', 'name' => 'Bloc Principal',
        ]);

        // ── 2. FILIÈRES ───────────────────────────────────────────────────────
        $filieres = [
            ['name' => 'Génie Civil',                               'abbr' => 'GC'],
            ['name' => 'Génie Electrique',                          'abbr' => 'GE'],
            ['name' => 'Géomètre Topographe',                       'abbr' => 'GT'],
            ['name' => 'Production Animale',                        'abbr' => 'PA'],
            ['name' => 'Production Végétale',                       'abbr' => 'PV'],
            ['name' => 'Génie de l\'Environnement',                 'abbr' => 'GEnv'],
            ['name' => 'Hygiène et Contrôle de Qualité',            'abbr' => 'HCQ'],
            ['name' => 'Biohygiène et Sécurité Sanitaire',          'abbr' => 'BSS'],
            ['name' => 'Analyses Biomédicales',                     'abbr' => 'ABM'],
            ['name' => 'Nutrition, Diététique et Tech. Alimentaire', 'abbr' => 'NDTA'],
            ['name' => 'Génie Rural',                               'abbr' => 'GR'],
            ['name' => 'Maintenance Industrielle',                  'abbr' => 'MI'],
            ['name' => 'Mécanique Automobile',                      'abbr' => 'MA'],
            ['name' => 'Hydraulique',                               'abbr' => 'HYD'],
            ['name' => 'Fabrication Mécanique',                     'abbr' => 'FM'],
            ['name' => 'Froid et Climatisation',                    'abbr' => 'FC'],
            ['name' => 'Génie Mécanique et Energétique',            'abbr' => 'GME'],
            ['name' => 'Génie Mécanique et Productique',            'abbr' => 'GMP'],
        ];

        $days    = ['monday','tuesday','wednesday','thursday','friday'];
        $creneaux = [
            ['start'=>'08:00:00','end'=>'10:00:00'],
            ['start'=>'10:00:00','end'=>'12:00:00'],
            ['start'=>'13:00:00','end'=>'15:00:00'],
            ['start'=>'15:00:00','end'=>'17:00:00'],
            ['start'=>'17:00:00','end'=>'19:00:00'],
        ];
        $dayToCarbon = [
            'monday'=>Carbon::MONDAY, 'tuesday'=>Carbon::TUESDAY,
            'wednesday'=>Carbon::WEDNESDAY, 'thursday'=>Carbon::THURSDAY,
            'friday'=>Carbon::FRIDAY,
        ];

        // ── Taux de présence par mois de l'année académique ──────────────────
        // Oct→Juin : taux réalistes, Juillet/Août = vacances
        $monthlyRate = [
            10 => 0.90, // Octobre  (rentrée, bon taux)
            11 => 0.87, // Novembre
            12 => 0.82, // Décembre (approche des fêtes)
            1  => 0.80, // Janvier  (reprise)
            2  => 0.78, // Février
            3  => 0.75, // Mars
            4  => 0.72, // Avril
            5  => 0.68, // Mai      (fatigue de fin d'année)
            6  => 0.65, // Juin     (examens, moins de cours)
        ];
        $lateRate = 0.15; // 15% des présents sont en retard

        $niveaux           = ['L1', 'L2', 'L3'];
        $attendanceInserts = [];

        foreach ($filieres as $idx => $f) {

            $deptId = DB::table('departments')->insertGetId([
                'uuid'=>Str::uuid(), 'name'=>$f['name'], 'abbreviation'=>$f['abbr'],
                'is_active'=>1, 'created_at'=>now(), 'updated_at'=>now(),
            ]);
            $roomId = DB::table('rooms')->insertGetId([
                'uuid'=>Str::uuid(), 'code'=>'R-'.$f['abbr'], 'name'=>'Salle '.$f['abbr'],
                'building_id'=>$buildingId, 'capacity'=>50,
            ]);
            $ueId = DB::table('teaching_units')->insertGetId([
                'uuid'=>Str::uuid(), 'code'=>'UE-'.$f['abbr'], 'name'=>'UE '.$f['name'],
            ]);
            $courseId = DB::table('course_elements')->insertGetId([
                'uuid'=>Str::uuid(), 'name'=>'Module '.$f['abbr'], 'code'=>'M-'.$f['abbr'],
                'credits'=>6, 'teaching_unit_id'=>$ueId,
            ]);

            $creneau = $creneaux[$idx % count($creneaux)];

            foreach (['2024-2025', '2025-2026'] as $yearLabel) {
                $yId      = $yearIds[$yearLabel];
                $baseYear = (int) explode('-', $yearLabel)[0];

                DB::table('course_element_professor')->insert([
                    'course_element_id'=>$courseId, 'professor_id'=>$profId,
                    'academic_year_id'=>$yId, 'is_primary'=>1,
                    'created_at'=>now(), 'updated_at'=>now(),
                ]);
                $cepId = DB::table('course_element_professor')
                    ->where('course_element_id', $courseId)
                    ->where('professor_id', $profId)
                    ->where('academic_year_id', $yId)
                    ->value('id');

                foreach ($niveaux as $nivIdx => $niv) {

                    $gId = DB::table('class_groups')->insertGetId([
                        'uuid'=>Str::uuid(), 'academic_year_id'=>$yId,
                        'department_id'=>$deptId, 'study_level'=>$niv,
                        'group_name'=>"{$f['abbr']}-{$niv}",
                        'created_at'=>now(), 'updated_at'=>now(),
                    ]);

                    $programId = DB::table('programs')->insertGetId([
                        'uuid'=>Str::uuid(), 'academic_year_id'=>$yId, 'semester'=>1,
                        'class_group_id'=>$gId, 'course_element_professor_id'=>$cepId,
                        'created_at'=>now(), 'updated_at'=>now(),
                    ]);

                    // Jour du créneau (varie par filière + niveau pour diversifier)
                    $dayName = $days[($idx + $nivIdx) % 5];
                    $edtRow  = [
                        'uuid'=>Str::uuid(), 'academic_year_id'=>$yId,
                        'department_id'=>$deptId, 'class_group_id'=>$gId,
                        'program_id'=>$programId, 'room_id'=>$roomId,
                        'day_of_week'=>$dayName,
                        'start_time'=>$creneau['start'], 'end_time'=>$creneau['end'],
                        'is_recurring'=>1, 'is_cancelled'=>0, 'is_active'=>1,
                        'created_at'=>now(), 'updated_at'=>now(),
                    ];
                    if (Schema::hasColumn('emploi_du_temps', 'course_element_id')) {
                        $edtRow['course_element_id'] = $courseId;
                    }
                    DB::table('emploi_du_temps')->insert($edtRow);

                    // Numéro Carbon du jour de ce créneau
                    $weekDay = $dayToCarbon[$dayName];

                    for ($i = 1; $i <= 3; $i++) {
                        $mat = "{$baseYear}-{$f['abbr']}-{$niv}-{$i}";
                        $sId = DB::table('students')->insertGetId([
                            'uuid'=>Str::uuid(), 'student_id_number'=>'ID-'.$mat,
                            'password'=>Hash::make('password'),
                            'first_name'=>'Etudiant'.$i, 'last_name'=>strtoupper($f['abbr']),
                            'matricule'=>$mat, 'niveau'=>$niv,
                            'filiere_id'=>$deptId, 'academic_year_id'=>$yId,
                            'fingerprint_status'=>0, 'fingerprint_index'=>null,
                            'created_at'=>now(), 'updated_at'=>now(),
                        ]);

                        // ══════════════════════════════════════════════════════
                        //  PRÉSENCES SUR TOUTE L'ANNÉE : Oct → Juin
                        //  Pour chaque mois : trouver les dates correspondant
                        //  au jour du créneau et générer des présences réalistes
                        // ══════════════════════════════════════════════════════
                        foreach ($monthlyRate as $month => $rate) {
                            // Calculer l'année calendaire du mois
                            // Mois 10,11,12 → baseYear ; Mois 1-6 → baseYear+1
                            $calYear = ($month >= 10) ? $baseYear : ($baseYear + 1);

                            // Ne pas générer des dates futures
                            $firstOfMonth = Carbon::create($calYear, $month, 1);
                            if ($firstOfMonth->isFuture()) continue;

                            // Trouver toutes les dates du bon jour dans ce mois
                            $cursor = $firstOfMonth->copy()->next($weekDay);
                            // Si le 1er est déjà ce jour, on commence au 1er
                            if ($firstOfMonth->dayOfWeek === $weekDay) {
                                $cursor = $firstOfMonth->copy();
                            }

                            $seancesInMonth = 0;
                            while ($cursor->month === $month && $seancesInMonth < 4) {
                                // Ne pas générer des dates futures
                                if ($cursor->isFuture()) {
                                    $cursor->addWeek();
                                    continue;
                                }

                                $isPresent = (mt_rand(0, 100) / 100) <= $rate;
                                $isLate    = $isPresent && (mt_rand(0, 100) / 100) <= $lateRate;

                                $attendanceInserts[] = [
                                    'student_id'        => $sId,
                                    'course_element_id' => $courseId,
                                    'room_id'           => $roomId,
                                    'date'              => $cursor->format('Y-m-d'),
                                    'status'            => $isPresent ? 'present' : 'absent',
                                    'on_time'           => ($isPresent && !$isLate) ? 1 : 0,
                                    'created_at'        => now(),
                                    'updated_at'        => now(),
                                ];

                                $seancesInMonth++;
                                $cursor->addWeek();

                                if (count($attendanceInserts) >= 500) {
                                    DB::table('attendances')->insert($attendanceInserts);
                                    $attendanceInserts = [];
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($attendanceInserts)) {
            DB::table('attendances')->insert($attendanceInserts);
        }

        // ── Créneau test AUJOURD'HUI ──────────────────────────────────────────
        $today    = strtolower(now()->locale('en')->dayName);
        $startNow = now()->subMinutes(3)->format('H:i:s');
        $endNow   = now()->addHours(2)->format('H:i:s');

        $tDept    = DB::table('departments')->where('abbreviation', 'GC')->first();
        $tRoom    = DB::table('rooms')->where('code', 'R-GC')->first();
        $tCourse  = DB::table('course_elements')->where('code', 'M-GC')->first();
        $tGroup   = DB::table('class_groups')
            ->where('department_id', $tDept->id)
            ->where('study_level', 'L1')
            ->where('academic_year_id', $yearIds['2025-2026'])->first();
        $tProgram = DB::table('programs')->where('class_group_id', $tGroup->id)->first();

        if ($tGroup && $tProgram) {
            $testRow = [
                'uuid'=>Str::uuid(), 'academic_year_id'=>$yearIds['2025-2026'],
                'department_id'=>$tDept->id, 'class_group_id'=>$tGroup->id,
                'program_id'=>$tProgram->id, 'room_id'=>$tRoom->id,
                'day_of_week'=>$today, 'start_time'=>$startNow, 'end_time'=>$endNow,
                'is_recurring'=>0, 'is_cancelled'=>0, 'is_active'=>1,
                'created_at'=>now(), 'updated_at'=>now(),
            ];
            if (Schema::hasColumn('emploi_du_temps', 'course_element_id')) {
                $testRow['course_element_id'] = $tCourse->id;
            }
            DB::table('emploi_du_temps')->insert($testRow);
        }

        // ── RÉSUMÉ ────────────────────────────────────────────────────────────
        $total    = DB::table('attendances')->count();
        $presents = DB::table('attendances')->where('status','present')->count();
        $retards  = DB::table('attendances')->where('status','present')->where('on_time',0)->count();
        $absents  = $total - $presents;

        // Vérifier la distribution par mois
        $byMonth = DB::table('attendances')
            ->selectRaw('MONTH(date) as m, COUNT(*) as cnt')
            ->groupBy('m')->orderBy('m')->get();

        $this->command->info('');
        $this->command->info('Résumé :');
        $this->command->info('  • ' . DB::table('students')->count()        . ' étudiants');
        $this->command->info('  • ' . $total    . ' présences au total');
        $this->command->info('    → ' . $presents . ' présents (' . $retards . ' en retard)');
        $this->command->info('    → ' . $absents  . ' absents');
        $this->command->info('  • ' . DB::table('emploi_du_temps')->count() . ' créneaux EDT');
        $this->command->info('');
        $this->command->info('Distribution par mois :');
        $moisLabels = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',
                       7=>'Juil',8=>'Août',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];
        foreach ($byMonth as $row) {
            $this->command->info("  Mois " . str_pad($moisLabels[$row->m] ?? $row->m, 5) . " : {$row->cnt} enregistrements");
        }
        $this->command->info('');
        $this->command->info('Dashboard 12 mois prêt !');
    }
}
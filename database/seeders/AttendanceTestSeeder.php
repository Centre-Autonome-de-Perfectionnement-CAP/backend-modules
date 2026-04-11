<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttendanceTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Initialisation complète — 3 filières, 3 années, L1/L2/L3...');

        // ─────────────────────────────────────────────────────────────
        // 1. NETTOYAGE
        // ─────────────────────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tables = [
            'attendances', 'emploi_du_temps', 'scheduled_courses',
            'time_slots', 'programs', 'course_element_professor',
            'class_groups', 'students', 'course_elements',
            'teaching_units', 'rooms', 'buildings',
            'departments', 'academic_years', 'professors',
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ─────────────────────────────────────────────────────────────
        // 2. ANNÉES ACADÉMIQUES
        // ─────────────────────────────────────────────────────────────
        $yearsData = [
            ['label' => '2023-2024', 'start' => '2023-09-01', 'end' => '2024-06-30'],
            ['label' => '2024-2025', 'start' => '2024-09-01', 'end' => '2025-06-30'],
            ['label' => '2025-2026', 'start' => '2025-09-01', 'end' => '2026-06-30'],
        ];
        $yearIds = [];
        foreach ($yearsData as $y) {
            $yearIds[$y['label']] = DB::table('academic_years')->insertGetId([
                'uuid'          => Str::uuid(),
                'academic_year' => $y['label'],
                'year_start'    => $y['start'],
                'year_end'      => $y['end'],
                'created_at'    => now(), 'updated_at' => now(),
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 3. FILIÈRES (Licence professionnelle)
        // ─────────────────────────────────────────────────────────────
        $filieresData = [
            ['name' => 'Génie civil (Licence professionnelle)',         'abbr' => 'GC-LP'],
            ['name' => 'Génie électrique (Licence professionnelle)',    'abbr' => 'GE-LP'],
            ['name' => 'Géomètre topographe (Licence professionnelle)', 'abbr' => 'GT-LP'],
        ];
        $deptIds = [];
        foreach ($filieresData as $f) {
            $deptIds[$f['abbr']] = DB::table('departments')->insertGetId([
                'uuid'         => Str::uuid(),
                'name'         => $f['name'],
                'abbreviation' => $f['abbr'],
                'is_active'    => 1,
                'created_at'   => now(), 'updated_at' => now(),
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 4. INFRASTRUCTURE
        // ─────────────────────────────────────────────────────────────
        $buildingId = DB::table('buildings')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'A', 'name' => 'Bloc A',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $roomByFil = [];
        $roomsData = [
            'GC-LP' => ['R01', 'Salle 01', 40],
            'GE-LP' => ['R02', 'Salle 02', 35],
            'GT-LP' => ['R03', 'Salle 03', 30],
        ];
        foreach ($roomsData as $abbr => $r) {
            $roomByFil[$abbr] = DB::table('rooms')->insertGetId([
                'uuid'        => Str::uuid(), 'code' => $r[0], 'name' => $r[1],
                'capacity'    => $r[2], 'building_id' => $buildingId,
                'created_at'  => now(), 'updated_at' => now(),
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 5. PROFESSEURS — un par filière
        // ─────────────────────────────────────────────────────────────
        $profsData = [
            'GC-LP' => ['Pierre',  'AHOUNOU',  'p.ahounou@epac.bj'],
            'GE-LP' => ['Marie',   'ZINSOU',   'm.zinsou@epac.bj'],
            'GT-LP' => ['Léonce',  'HOUNSOU',  'l.hounsou@epac.bj'],
        ];
        $profByFil = [];
        foreach ($profsData as $abbr => $p) {
            $profByFil[$abbr] = DB::table('professors')->insertGetId([
                'uuid'       => Str::uuid(), 'first_name' => $p[0],
                'last_name'  => $p[1], 'email' => $p[2],
                'status'     => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 6. COURS — un par filière
        // ─────────────────────────────────────────────────────────────
        $coursesData = [
            'GC-LP' => ['UE-GC', 'Résistance des matériaux', 'RDM-01'],
            'GE-LP' => ['UE-GE', 'Électronique de puissance', 'ELEC-01'],
            'GT-LP' => ['UE-GT', 'Topographie avancée',       'TOPO-01'],
        ];
        $courseByFil = [];
        foreach ($coursesData as $abbr => $c) {
            $ueId = DB::table('teaching_units')->insertGetId([
                'uuid' => Str::uuid(), 'code' => $c[0], 'name' => $c[1],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $courseByFil[$abbr] = DB::table('course_elements')->insertGetId([
                'uuid'             => Str::uuid(), 'name' => $c[1], 'code' => $c[2],
                'teaching_unit_id' => $ueId, 'credits' => 5,
                'hours'            => 40, 'coefficient' => 3,
                'created_at'       => now(), 'updated_at' => now(),
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 7. CRÉNEAUX — 2 par filière sur la semaine
        // ─────────────────────────────────────────────────────────────
        $creneaux = [
            'GC-LP' => [
                ['monday',    '08:00:00', '12:00:00', 'Lundi matin'],
                ['wednesday', '10:00:00', '14:00:00', 'Mercredi matin'],
            ],
            'GE-LP' => [
                ['tuesday',  '08:00:00', '12:00:00', 'Mardi matin'],
                ['thursday', '14:00:00', '18:00:00', 'Jeudi après-midi'],
            ],
            'GT-LP' => [
                ['monday',  '14:00:00', '18:00:00', 'Lundi après-midi'],
                ['friday',  '08:00:00', '12:00:00', 'Vendredi matin'],
            ],
        ];

        // Time slots (partagés entre années)
        $timeSlotByFil = [];
        foreach ($creneaux as $abbr => $slots) {
            $timeSlotByFil[$abbr] = [];
            foreach ($slots as $slot) {
                $timeSlotByFil[$abbr][] = DB::table('time_slots')->insertGetId([
                    'uuid'        => Str::uuid(), 'name' => $slot[3],
                    'day_of_week' => $slot[0],
                    'start_time'  => $slot[1], 'end_time' => $slot[2],
                    'type'        => 'lecture',
                    'created_at'  => now(), 'updated_at' => now(),
                ]);
            }
        }

        $niveaux = ['L1', 'L2', 'L3'];

        // ─────────────────────────────────────────────────────────────
        // 8. BOUCLE PRINCIPALE : année × filière
        //
        //    ✅ course_element_professor : UNE SEULE insertion par
        //       (course_element_id, professor_id, academic_year_id)
        //       puis le même cepId est réutilisé pour chaque niveau.
        //
        //    La contrainte unique est sur (course_element_id, professor_id).
        //    On insère donc une seule fois par filière × année.
        // ─────────────────────────────────────────────────────────────
        $groupIds = [];

        foreach ($yearsData as $y) {
            $yearLabel = $y['label'];
            $yId       = $yearIds[$yearLabel];
            [$startYear] = explode('-', $yearLabel);
            $startYear    = (int) $startYear;

            foreach ($filieresData as $fi) {
                $abbr     = $fi['abbr'];
                $deptId   = $deptIds[$abbr];
                $courseId = $courseByFil[$abbr];
                $profId   = $profByFil[$abbr];
                $roomId   = $roomByFil[$abbr];

                // ✅ UN SEUL course_element_professor par filière × année
                $cepId = DB::table('course_element_professor')->insertGetId([
                    'course_element_id' => $courseId,
                    'professor_id'      => $profId,
                    'is_primary'        => 1,
                    'academic_year_id'  => $yId,
                    // class_group_id est optionnel ici — on le laisse NULL
                    // pour éviter toute contrainte unique supplémentaire
                    'created_at'        => now(), 'updated_at' => now(),
                ]);

                foreach ($niveaux as $niv) {
                    // Groupe de classe
                    $gId = DB::table('class_groups')->insertGetId([
                        'uuid'             => Str::uuid(),
                        'academic_year_id' => $yId,
                        'department_id'    => $deptId,
                        'study_level'      => $niv,
                        'group_name'       => "{$abbr}-{$niv}",
                        'created_at'       => now(), 'updated_at' => now(),
                    ]);
                    $groupIds[$yearLabel][$abbr][$niv] = $gId;

                    // Programme — utilise le même cepId pour tous les niveaux
                    $pId = DB::table('programs')->insertGetId([
                        'uuid'                        => Str::uuid(),
                        'academic_year_id'            => $yId,
                        'semester'                    => 1,
                        'class_group_id'              => $gId,
                        'course_element_professor_id' => $cepId,
                        'created_at'                  => now(), 'updated_at' => now(),
                    ]);

                    // Emploi du temps — 2 créneaux par groupe
                    foreach ($creneaux[$abbr] as $slot) {
                        DB::table('emploi_du_temps')->insert([
                            'uuid'             => Str::uuid(),
                            'academic_year_id' => $yId,
                            'department_id'    => $deptId,
                            'class_group_id'   => $gId,
                            'program_id'       => $pId,
                            'room_id'          => $roomId,
                            'day_of_week'      => $slot[0],
                            'start_time'       => $slot[1],
                            'end_time'         => $slot[2],
                            'is_recurring'     => 1,
                            'is_cancelled'     => 0,
                            'is_active'        => 1,
                            'created_at'       => now(), 'updated_at' => now(),
                        ]);
                    }

                    // Scheduled courses
                    foreach ($timeSlotByFil[$abbr] as $tsId) {
                        DB::table('scheduled_courses')->insert([
                            'uuid'         => Str::uuid(),
                            'program_id'   => $pId,
                            'time_slot_id' => $tsId,
                            'room_id'      => $roomId,
                            'start_date'   => $y['start'],
                            'end_date'     => $y['end'],
                            'is_cancelled' => 0,
                            'created_at'   => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 9. ÉTUDIANTS + PRÉSENCES SUR 12 MOIS
        // ─────────────────────────────────────────────────────────────

        $firstNames = [
            'Armel','Lucie','Marc','Flore','Serge',
            'Nadia','Rodrigue','Elvire','Boris','Clarisse',
            'Désiré','Estelle','Fernand','Grâce','Hervé',
        ];
        $lastNames = [
            'SOSSOU','AGOSSOU','TOKOUDJI','DOSSOU','HOUETO',
            'ZANNOU','KPODO','GOUDOTE','AZONHOU','BOSSOU',
            'CAPO','DAKPOGAN','ELEGBEDE','FAGLA','GANYE',
        ];

        // Taux de présence réalistes par mois (0.0 = pas de cours)
        $monthlyRates = [
            1 => 0.82, 2 => 0.78, 3 => 0.75, 4 => 0.80,
            5 => 0.70, 6 => 0.65, 7 => 0.0,  8 => 0.0,
            9 => 0.90, 10 => 0.88, 11 => 0.85, 12 => 0.72,
        ];

        // Jours de cours par filière
        $daysByFil = [
            'GC-LP' => ['monday', 'wednesday'],
            'GE-LP' => ['tuesday', 'thursday'],
            'GT-LP' => ['monday', 'friday'],
        ];
        $carbonDayMap = [
            'monday'    => Carbon::MONDAY,
            'tuesday'   => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday'  => Carbon::THURSDAY,
            'friday'    => Carbon::FRIDAY,
        ];

        $matNum            = 1000;
        $attendanceInserts = [];

        foreach ($yearsData as $y) {
            $yearLabel  = $y['label'];
            $yId        = $yearIds[$yearLabel];
            [$startYear] = explode('-', $yearLabel);
            $startYear    = (int) $startYear;

            // Pré-calculer les dates de cours par mois × jour
            $datesByMonthDay = [];
            $allDays = array_unique(array_merge(...array_values($daysByFil)));
            for ($m = 1; $m <= 12; $m++) {
                if ($monthlyRates[$m] === 0.0) continue;
                $calYear = ($m >= 9) ? $startYear : $startYear + 1;
                $datesByMonthDay[$m] = [];
                foreach ($allDays as $dayName) {
                    $carbonDay = $carbonDayMap[$dayName];
                    $datesByMonthDay[$m][$dayName] = [];
                    $cur = Carbon::create($calYear, $m, 1)->startOfMonth();
                    while ($cur->month === $m) {
                        if ($cur->dayOfWeek === $carbonDay) {
                            $datesByMonthDay[$m][$dayName][] = $cur->format('Y-m-d');
                        }
                        $cur->addDay();
                    }
                }
            }

            foreach ($filieresData as $fi) {
                $abbr     = $fi['abbr'];
                $deptId   = $deptIds[$abbr];
                $courseId = $courseByFil[$abbr];
                $roomId   = $roomByFil[$abbr];
                $days     = $daysByFil[$abbr];

                foreach ($niveaux as $niv) {
                    $gId = $groupIds[$yearLabel][$abbr][$niv];

                    // 5 étudiants par groupe
                    for ($n = 0; $n < 5; $n++) {
                        $matNum++;
                        $fname = $firstNames[$matNum % count($firstNames)];
                        $lname = $lastNames[$matNum  % count($lastNames)];
                        $phone = '+229 9' . str_pad($matNum % 10000000, 7, '0', STR_PAD_LEFT);
                        $fp    = ($n % 3 !== 0) ? 1 : 0;

                        $sId = DB::table('students')->insertGetId([
                            'uuid'               => Str::uuid(),
                            'student_id_number'  => 'STU-' . $matNum,
                            'password'           => Hash::make('password'),
                            'first_name'         => $fname,
                            'last_name'          => $lname,
                            'matricule'          => $matNum . '-' . ($startYear + 1),
                            'phone'              => $phone,
                            'niveau'             => $niv,
                            'fingerprint_status' => $fp,
                            'filiere_id'         => $deptId,
                            'academic_year_id'   => $yId,
                            'created_at'         => now(), 'updated_at' => now(),
                        ]);

                        // Présences sur 12 mois
                        for ($m = 1; $m <= 12; $m++) {
                            $rate = $monthlyRates[$m];
                            if ($rate === 0.0 || empty($datesByMonthDay[$m])) continue;

                            foreach ($days as $dayName) {
                                foreach ($datesByMonthDay[$m][$dayName] ?? [] as $dateStr) {
                                    $attendanceInserts[] = [
                                        'student_id'        => $sId,
                                        'course_element_id' => $courseId,
                                        'room_id'           => $roomId,
                                        'status'            => (mt_rand(0, 100) / 100) <= $rate
                                                               ? 'present' : 'absent',
                                        'date'              => $dateStr,
                                        'created_at'        => now(),
                                        'updated_at'        => now(),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Insérer par lots de 300
        foreach (array_chunk($attendanceInserts, 300) as $chunk) {
            DB::table('attendances')->insert($chunk);
        }

        $total   = count($attendanceInserts);
        $present = count(array_filter($attendanceInserts, fn($a) => $a['status'] === 'present'));

        $this->command->info('');
        $this->command->info('✅ Seeder terminé avec succès !');
        $this->command->info('');
        $this->command->info('📋 FILIÈRES :');
        foreach ($filieresData as $f) {
            $this->command->info("   - {$f['name']}");
        }
        $this->command->info('');
        $this->command->info('📅 ANNÉES : 2023-2024 / 2024-2025 / 2025-2026');
        $this->command->info('🎓 NIVEAUX : L1 / L2 / L3');
        $this->command->info('');
        $this->command->info('⏰ CRÉNEAUX :');
        $this->command->info('   GC-LP : Lundi 08:00-12:00  +  Mercredi 10:00-14:00');
        $this->command->info('   GE-LP : Mardi 08:00-12:00  +  Jeudi 14:00-18:00');
        $this->command->info('   GT-LP : Lundi 14:00-18:00  +  Vendredi 08:00-12:00');
        $this->command->info('');
        $this->command->info("👥 ÉTUDIANTS : {$matNum} (5 par groupe)");
        $this->command->info("📊 PRÉSENCES : {$total} enregistrements ({$present} présents)");
        $this->command->info('');
        $this->command->info('→ Dashboard : Jan-Déc avec taux réalistes ✓');
        $this->command->info('→ Filtre Heure par créneau (Lundi/Mercredi/etc.) ✓');
        $this->command->info('→ Filtres Filière, Niveau, Année fonctionnels ✓');
    }
}
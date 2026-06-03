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
        $this->command->info(' 🚀 Amorçage EPAC — données complètes avec pointage...');

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

        // ── ANNÉES ACADÉMIQUES (2003 - 2026) ─────────────────────────────────
        $yearIds = [];
        foreach (range(2003, 2026) as $start) {
            $end   = $start + 1;
            $label = "{$start}-{$end}";
            $yearIds[$label] = DB::table('academic_years')->insertGetId([
                'uuid'          => Str::uuid(),
                'academic_year' => $label,
                'year_start'    => "{$start}-10-01",
                'year_end'      => "{$end}-06-30",
                'is_current'    => ($label === '2025-2026' ? 1 : 0),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->command->info(' ' . count($yearIds) . ' années académiques créées (2003-2027)');

        // ── PROFESSEUR + BÂTIMENT ────────────────────────────────────────────
        $profId = DB::table('professors')->insertGetId([
            'uuid' => Str::uuid(), 'first_name' => 'Admin', 'last_name' => 'EPAC',
            'email' => 'admin@epac.bj', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $buildingId = DB::table('buildings')->insertGetId([
            'uuid' => Str::uuid(), 'code' => 'A', 'name' => 'Bloc Principal',
        ]);

        // ── FILIÈRES EPAC ────────────────────────────────────────────────────
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

        $days     = ['monday','tuesday','wednesday','thursday','friday'];
        $creneaux = [
            ['start' => '08:00:00', 'end' => '10:00:00'],
            ['start' => '10:00:00', 'end' => '12:00:00'],
            ['start' => '13:00:00', 'end' => '15:00:00'],
            ['start' => '15:00:00', 'end' => '17:00:00'],
            ['start' => '17:00:00', 'end' => '19:00:00'],
        ];
        $dayToCarbon = [
            'monday'    => Carbon::MONDAY,    'tuesday'  => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY, 'thursday' => Carbon::THURSDAY,
            'friday'    => Carbon::FRIDAY,
        ];

        $monthlyRate = [
            10 => 0.90, 11 => 0.87, 12 => 0.82,
            1  => 0.80, 2  => 0.78, 3  => 0.75,
            4  => 0.72, 5  => 0.68, 6  => 0.65,
        ];
        $lateRate = 0.15;

        $hasScanTime = Schema::hasColumn('attendances', 'scan_time');

        $firstNames = ['Armel','Lucie','Marc','Flore','Serge','Nadia','Rodrigue',
                       'Elvire','Boris','Clarisse','Désiré','Estelle','Kevin','Aïcha',
                       'Patrick','Sandrine','Hervé','Joëlle','Wilfried','Carine'];
        $lastNames  = ['SOSSOU','AGOSSOU','TOKOUDJI','DOSSOU','HOUETO','ZANNOU',
                       'KPODO','GOUDOTE','AZONHOU','BOSSOU','ADANLETE','GBAGUIDI',
                       'AMOUSSOU','HONFO','VODOUNOU','ASSOGBA','HOUNWANOU','GNIMAVO'];

        $niveaux           = ['L1', 'L2', 'L3'];
        $attendanceInserts = [];
        $matNum            = 1000;
        
        // On initialise le compteur d'empreintes
        $fingerprintCounter = 1;

        foreach ($filieres as $idx => $f) {

            $deptId = DB::table('departments')->insertGetId([
                'uuid' => Str::uuid(), 'name' => $f['name'],
                'abbreviation' => $f['abbr'], 'is_active' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $roomId = DB::table('rooms')->insertGetId([
                'uuid' => Str::uuid(), 'code' => 'R-'.$f['abbr'],
                'name' => 'Salle '.$f['abbr'], 'building_id' => $buildingId, 'capacity' => 50,
            ]);

            $ueId = DB::table('teaching_units')->insertGetId([
                'uuid' => Str::uuid(), 'code' => 'UE-'.$f['abbr'], 'name' => 'UE '.$f['name'],
            ]);

            $courseId = DB::table('course_elements')->insertGetId([
                'uuid' => Str::uuid(), 'name' => 'Module '.$f['abbr'],
                'code' => 'M-'.$f['abbr'], 'credits' => 6, 'teaching_unit_id' => $ueId,
            ]);

            $creneau = $creneaux[$idx % count($creneaux)];
            $dayName = $days[$idx % 5];

            $cepIds = [];
            foreach ($yearIds as $yearLabel => $yId) {
                $existingCep = DB::table('course_element_professor')
                    ->where('course_element_id', $courseId)
                    ->when(Schema::hasColumn('course_element_professor', 'academic_year_id'), function($query) use ($yId) {
                        return $query->where('academic_year_id', $yId);
                    })
                    ->first();

                if ($existingCep) {
                    $cepIds[$yearLabel] = $existingCep->id;
                } else {
                    try {
                        $cepData = [
                            'course_element_id' => $courseId,
                            'professor_id'      => $profId,
                            'is_primary'        => 1,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                        if (Schema::hasColumn('course_element_professor', 'academic_year_id')) {
                            $cepData['academic_year_id'] = $yId;
                        }
                        
                        $cepIds[$yearLabel] = DB::table('course_element_professor')->insertGetId($cepData);
                    } catch (\Exception $e) {
                        $cepIds[$yearLabel] = DB::table('course_element_professor')
                            ->where('course_element_id', $courseId)
                            ->value('id');
                    }
                }
            }

            foreach ($yearIds as $yearLabel => $yId) {
                $baseYear = (int)explode('-', $yearLabel)[0];
                $cepId = $cepIds[$yearLabel] ?? DB::table('course_element_professor')->where('course_element_id', $courseId)->value('id');

                foreach ($niveaux as $nivIdx => $niv) {

                    $gId = DB::table('class_groups')->insertGetId([
                        'uuid'             => Str::uuid(),
                        'academic_year_id' => $yId,
                        'department_id'    => $deptId,
                        'study_level'      => $niv,
                        'group_name'       => "{$f['abbr']}-{$niv}",
                        'created_at'    => now(), 'updated_at' => now(),
                    ]);

                    $programId = DB::table('programs')->insertGetId([
                        'uuid'                        => Str::uuid(),
                        'academic_year_id'            => $yId,
                        'semester'                    => 1,
                        'class_group_id'              => $gId,
                        'course_element_professor_id' => $cepId,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);

                    // Emploi du temps
                    $edtDay  = $days[($idx + $nivIdx) % 5];
                    $edtWDay = $dayToCarbon[$edtDay];
                    $edtRow  = [
                        'uuid'             => Str::uuid(),
                        'academic_year_id' => $yId,
                        'department_id'    => $deptId,
                        'class_group_id'   => $gId,
                        'program_id'       => $programId,
                        'room_id'          => $roomId,
                        'day_of_week'      => $edtDay,
                        'start_time'       => $creneau['start'],
                        'end_time'         => $creneau['end'],
                        'is_recurring'     => 1,
                        'is_cancelled'     => 0,
                        'is_active'        => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                    if (Schema::hasColumn('emploi_du_temps', 'course_element_id')) {
                        $edtRow['course_element_id'] = $courseId;
                    }
                    DB::table('emploi_du_temps')->insert($edtRow);

                    // Génération des données de pointage pour l'historique récent
                    if ($baseYear < 2024) continue;

                    for ($i = 1; $i <= 5; $i++) {
                        $matNum++;
                        $fnIdx = $matNum % count($firstNames);
                        $lnIdx = ($matNum + $i) % count($lastNames);
                        
                        $hasFingerprint = ($i <= 3);
                        $fIndex = null;
                        
                        if ($hasFingerprint) {
                            // SÉCURISATION RANGE ET UNICITÉ COMMUNE : 
                            // Reste toujours entre 1 et 125 pour s'adapter même aux colonnes TINYINT signées (-128 à 127)
                            $fIndex = ($fingerprintCounter % 125) + 1;
                            $fingerprintCounter++;
                        }

                        try {
                            $sId = DB::table('students')->insertGetId([
                                'uuid'               => Str::uuid(),
                                'student_id_number'  => 'STU-'.$matNum,
                                'password'           => Hash::make('password'),
                                'first_name'         => $firstNames[$fnIdx],
                                'last_name'          => $lastNames[$lnIdx],
                                'matricule'          => $matNum.'-'.($baseYear+1),
                                'phone'              => '+229 9'.str_pad($matNum % 10000000, 7, '0', STR_PAD_LEFT),
                                'niveau'             => $niv,
                                'filiere_id'         => $deptId,
                                'academic_year_id'   => $yId,
                                'fingerprint_status' => $hasFingerprint ? 1 : 0,
                                'fingerprint_index'  => $fIndex,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        } catch (\Exception $e) {
                            // Si la clef unique bloque à cause du modulo restreint, on passe l'index à null pour cet étudiant de test
                            $sId = DB::table('students')->insertGetId([
                                'uuid'               => Str::uuid(),
                                'student_id_number'  => 'STU-'.$matNum,
                                'password'           => Hash::make('password'),
                                'first_name'         => $firstNames[$fnIdx],
                                'last_name'          => $lastNames[$lnIdx],
                                'matricule'          => $matNum.'-'.($baseYear+1),
                                'phone'              => '+229 9'.str_pad($matNum % 10000000, 7, '0', STR_PAD_LEFT),
                                'niveau'             => $niv,
                                'filiere_id'         => $deptId,
                                'academic_year_id'   => $yId,
                                'fingerprint_status' => 0,
                                'fingerprint_index'  => null,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }

                        foreach ($monthlyRate as $month => $rate) {
                            $calYear = ($month >= 10) ? $baseYear : ($baseYear + 1);
                            $firstDay = Carbon::create($calYear, $month, 1);

                            $cursor = $firstDay->copy();
                            if ($cursor->dayOfWeek !== $edtWDay) {
                                $cursor = $cursor->next($edtWDay);
                            }

                            $seances = 0;
                            while ($cursor->month === $month && $seances < 4) {
                                $isPresent = (mt_rand(0, 100) / 100) <= $rate;
                                $isLate    = $isPresent && (mt_rand(0, 100) / 100) <= $lateRate;

                                $startH   = (int)substr($creneau['start'], 0, 2);
                                $startMin = (int)substr($creneau['start'], 3, 2);

                                if ($isPresent) {
                                    $offset   = $isLate ? mt_rand(6, 25) : mt_rand(-10, 4);
                                    $totalMin = $startH * 60 + $startMin + $offset;
                                    if ($totalMin < 0) $totalMin = 0;
                                    $scanTime = sprintf('%02d:%02d:%02d',
                                        intdiv($totalMin, 60) % 24,
                                        $totalMin % 60,
                                        mt_rand(0, 59)
                                    );
                                } else {
                                    $scanTime = null;
                                }

                                $row = [
                                    'student_id'        => $sId,
                                    'course_element_id' => $courseId,
                                    'room_id'           => $roomId,
                                    'date'              => $cursor->format('Y-m-d'),
                                    'status'            => $isPresent ? 'present' : 'absent',
                                    'on_time'           => ($isPresent && !$isLate) ? 1 : 0,
                                    'created_at'        => now(),
                                    'updated_at'        => now(),
                                ];
                                if ($hasScanTime) {
                                    $row['scan_time'] = $scanTime;
                                }

                                $attendanceInserts[] = $row;
                                $seances++;
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

        // ── CRÉNEAU EN DIRECT POUR AUJOURD'HUI ───────────────────────────────
        $today    = strtolower(now()->locale('en')->dayName);
        $startNow = now()->subMinutes(3)->format('H:i:s');
        $endNow   = now()->addHours(2)->format('H:i:s');

        $tDept    = DB::table('departments')->where('abbreviation', 'GC')->first();
        $tRoom    = DB::table('rooms')->where('code', 'R-GC')->first();
        $tCourse  = DB::table('course_elements')->where('code', 'M-GC')->first();
        $tGroup   = DB::table('class_groups')
            ->where('department_id', $tDept->id)
            ->where('study_level', 'L1')
            ->where('academic_year_id', $yearIds['2025-2026'])
            ->first();
        $tProgram = $tGroup ? DB::table('programs')->where('class_group_id', $tGroup->id)->first() : null;

        if ($tGroup && $tProgram) {
            $testRow = [
                'uuid'             => Str::uuid(),
                'academic_year_id' => $yearIds['2025-2026'],
                'department_id'    => $tDept->id,
                'class_group_id'   => $tGroup->id,
                'program_id'       => $tProgram->id,
                'room_id'          => $tRoom->id,
                'day_of_week'      => $today,
                'start_time'       => $startNow,
                'end_time'         => $endNow,
                'is_recurring'     => 0,
                'is_cancelled'     => 0,
                'is_active'        => 1,
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (Schema::hasColumn('emploi_du_temps', 'course_element_id')) {
                $testRow['course_element_id'] = $tCourse->id;
            }
            DB::table('emploi_du_temps')->insert($testRow);
        }

        // ── AFFICHAGE DES RÉSULTATS DANS LA CONSOLE ─────────────────────────
        $total    = DB::table('attendances')->count();
        $presents = DB::table('attendances')->where('status', 'present')->count();
        $retards  = DB::table('attendances')->where('status', 'present')->where('on_time', 0)->count();
        $absents  = $total - $presents;

        $byMonth = DB::table('attendances')
            ->selectRaw('MONTH(date) as m, COUNT(*) as cnt')
            ->groupBy('m')->orderBy('m')->get();

        $moisL = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',
                  7=>'Juil',8=>'Août',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];

        $this->command->info('');
        $this->command->info('📊 Résumé de la base de données :');
        $this->command->info('  • ' . count($yearIds) . ' années académiques (2003-2027)');
        $this->command->info('  • ' . DB::table('departments')->count() . ' filières EPAC');
        $this->command->info('  • ' . DB::table('students')->count() . ' étudiants');
        $this->command->info('  • ' . $total . ' lignes de pointages générées :');
        $this->command->info('      -> ' . $presents . ' présents');
        $this->command->info('      -> ' . $retards . ' retards');
        $this->command->info('      -> ' . $absents . ' absents');
        $this->command->info('  • ' . DB::table('emploi_du_temps')->count() . ' cours ajoutés à l\'EDT');
        $this->command->info('');
        $this->command->info('Répartition mensuelle des présences :');
        foreach ($byMonth as $row) {
            $bar = str_repeat('█', min(40, (int)($row->cnt / 100)));
            $this->command->info('  ' . str_pad($moisL[$row->m] ?? $row->m, 5) . " : {$bar} ({$row->cnt})");
        }
        $this->command->info('');
        $this->command->info('✅ Terminé ! Le range numérique et les index uniques sont totalement maîtrisés.');
    }
}
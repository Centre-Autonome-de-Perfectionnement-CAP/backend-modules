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
        $this->command->info('Amorçage EPAC — données complètes avec emploi du temps...');

        // ── NETTOYAGE ─────────────────────────────────────────────────────────
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
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
        $this->command->info('Années académiques créées : ' . count($yearIds));

        // ── 2. PROFESSEUR ─────────────────────────────────────────────────────
        $profId = DB::table('professors')->insertGetId([
            'uuid'       => Str::uuid(),
            'first_name' => 'Admin',
            'last_name'  => 'EPAC',
            'email'      => 'admin@epac.bj',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── 3. BÂTIMENT + SALLE COMMUNE ───────────────────────────────────────
        $buildingId = DB::table('buildings')->insertGetId([
            'uuid' => Str::uuid(),
            'code' => 'A',
            'name' => 'Bloc Principal',
        ]);

        // ── 4. FILIÈRES ───────────────────────────────────────────────────────
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

        // Jours et créneaux
        $days     = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $creneaux = [
            ['start' => '08:00:00', 'end' => '10:00:00'],
            ['start' => '10:00:00', 'end' => '12:00:00'],
            ['start' => '13:00:00', 'end' => '15:00:00'],
            ['start' => '15:00:00', 'end' => '17:00:00'],
            ['start' => '17:00:00', 'end' => '19:00:00'],
        ];

        $niveaux = ['L1', 'L2', 'L3'];
        $attendanceInserts = [];
        $totalEdtCreated   = 0;

        // ── COLONNES OBLIGATOIRES de emploi_du_temps ──────────────────────────
        // D'après votre phpMyAdmin :
        // id, uuid, academic_year_id(NOT NULL), department_id(NOT NULL),
        // class_group_id(NOT NULL), program_id(NOT NULL), room_id(NOT NULL),
        // day_of_week(NOT NULL), start_time(NOT NULL), end_time(NOT NULL),
        // is_recurring(défaut 1), is_cancelled(défaut 0), is_active(défaut 1)
        // + course_element_id (ajouté par notre migration, nullable)

        foreach ($filieres as $idx => $f) {

            // Entités fixes par filière
            $deptId = DB::table('departments')->insertGetId([
                'uuid'         => Str::uuid(),
                'name'         => $f['name'],
                'abbreviation' => $f['abbr'],
                'is_active'    => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $roomId = DB::table('rooms')->insertGetId([
                'uuid'        => Str::uuid(),
                'code'        => 'R-' . $f['abbr'],
                'name'        => 'Salle ' . $f['abbr'],
                'building_id' => $buildingId,
                'capacity'    => 50,
            ]);

            $ueId = DB::table('teaching_units')->insertGetId([
                'uuid' => Str::uuid(),
                'code' => 'UE-' . $f['abbr'],
                'name' => 'UE ' . $f['name'],
            ]);

            $courseId = DB::table('course_elements')->insertGetId([
                'uuid'             => Str::uuid(),
                'name'             => 'Module ' . $f['abbr'],
                'code'             => 'M-' . $f['abbr'],
                'credits'          => 6,
                'teaching_unit_id' => $ueId,
            ]);

            // Créneau de cette filière (rotation sur les jours et plages)
            $day     = $days[$idx % 5];
            $creneau = $creneaux[$idx % count($creneaux)];

            // Années actives
            foreach (['2024-2025', '2025-2026'] as $yearLabel) {
                $yId      = $yearIds[$yearLabel];
                $baseYear = (int) explode('-', $yearLabel)[0];

                // Liaison professeur ← cours
                DB::table('course_element_professor')->insert([
                    'course_element_id' => $courseId,
                    'professor_id'      => $profId,
                    'academic_year_id'  => $yId,
                    'is_primary'        => 1,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                foreach ($niveaux as $nivIdx => $niv) {

                    // Groupe de classe
                    $gId = DB::table('class_groups')->insertGetId([
                        'uuid'             => Str::uuid(),
                        'academic_year_id' => $yId,
                        'department_id'    => $deptId,
                        'study_level'      => $niv,
                        'group_name'       => "{$f['abbr']}-{$niv}",
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);

                    // Programme
                    $cepId = DB::table('course_element_professor')
                        ->where('course_element_id', $courseId)
                        ->where('professor_id', $profId)
                        ->where('academic_year_id', $yId)
                        ->value('id');

                    $programId = DB::table('programs')->insertGetId([
                        'uuid'                          => Str::uuid(),
                        'academic_year_id'              => $yId,
                        'semester'                      => 1,
                        'class_group_id'                => $gId,
                        'course_element_professor_id'   => $cepId,
                        'created_at'                    => now(),
                        'updated_at'                    => now(),
                    ]);

                    // ── EMPLOI DU TEMPS ───────────────────────────────────────
                    // Toutes les colonnes NOT NULL présentes
                    // course_element_id ajouté via notre migration (nullable)
                    $edtRow = [
                        'uuid'              => Str::uuid(),
                        'academic_year_id'  => $yId,           // NOT NULL
                        'department_id'     => $deptId,        // NOT NULL
                        'class_group_id'    => $gId,           // NOT NULL
                        'program_id'        => $programId,     // NOT NULL
                        'room_id'           => $roomId,        // NOT NULL
                        'day_of_week'       => $days[($idx + $nivIdx) % 5], // varie par niveau
                        'start_time'        => $creneau['start'],
                        'end_time'          => $creneau['end'],
                        'is_recurring'      => 1,
                        'is_cancelled'      => 0,
                        'is_active'         => 1,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];

                    // Ajouter course_element_id seulement si la colonne existe
                    if (Schema::hasColumn('emploi_du_temps', 'course_element_id')) {
                        $edtRow['course_element_id'] = $courseId;
                    }

                    DB::table('emploi_du_temps')->insert($edtRow);
                    $totalEdtCreated++;

                    // ── ÉTUDIANTS + PRÉSENCES ─────────────────────────────────
                    for ($i = 1; $i <= 3; $i++) {
                        $mat = "{$baseYear}-{$f['abbr']}-{$niv}-{$i}";
                        $sId = DB::table('students')->insertGetId([
                            'uuid'               => Str::uuid(),
                            'student_id_number'  => 'ID-' . $mat,
                            'password'           => Hash::make('password'),
                            'first_name'         => 'Etudiant' . $i,
                            'last_name'          => strtoupper($f['abbr']),
                            'matricule'          => $mat,
                            'niveau'             => $niv,
                            'filiere_id'         => $deptId,
                            'academic_year_id'   => $yId,
                            'fingerprint_status' => 0,
                            'fingerprint_index'  => null,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ]);

                        // Présences variées sur les 3 derniers mois
                        // Rotation : présent à l'heure / retard / absent
                        $presencePool = [
                            ['status' => 'present', 'on_time' => 1],
                            ['status' => 'present', 'on_time' => 1],
                            ['status' => 'present', 'on_time' => 0], // retard
                            ['status' => 'absent',  'on_time' => 1],
                        ];

                        for ($j = 0; $j < 8; $j++) {
                            $s = $presencePool[$j % count($presencePool)];
                            $attendanceInserts[] = [
                                'student_id'        => $sId,
                                'course_element_id' => $courseId,
                                'room_id'           => $roomId,
                                'date'              => now()->subDays(($j + 1) * 3)->format('Y-m-d'),
                                'status'            => $s['status'],
                                'on_time'           => $s['on_time'],
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ];
                        }

                        // Flush par lot de 500
                        if (count($attendanceInserts) >= 500) {
                            DB::table('attendances')->insert($attendanceInserts);
                            $attendanceInserts = [];
                        }
                    }
                }
            }
        }

        // Insérer le reste
        if (!empty($attendanceInserts)) {
            DB::table('attendances')->insert($attendanceInserts);
        }

        // ── 5. CRÉNEAU AUJOURD'HUI (test getSensorStatus en temps réel) ──────
        $today     = strtolower(now()->locale('en')->dayName);
        $startTest = now()->subMinutes(3)->format('H:i:s');
        $endTest   = now()->addHours(2)->format('H:i:s');

        $testDept   = DB::table('departments')->where('abbreviation', 'GC')->first();
        $testRoom   = DB::table('rooms')->where('code', 'R-GC')->first();
        $testCourse = DB::table('course_elements')->where('code', 'M-GC')->first();
        $testGroup  = DB::table('class_groups')
            ->where('department_id', $testDept->id)
            ->where('study_level', 'L1')
            ->where('academic_year_id', $yearIds['2025-2026'])
            ->first();
        $testProgram = DB::table('programs')
            ->where('class_group_id', $testGroup->id)
            ->first();

        $edtTestRow = [
            'uuid'             => Str::uuid(),
            'academic_year_id' => $yearIds['2025-2026'],
            'department_id'    => $testDept->id,
            'class_group_id'   => $testGroup->id,
            'program_id'       => $testProgram->id,
            'room_id'          => $testRoom->id,
            'day_of_week'      => $today,
            'start_time'       => $startTest,
            'end_time'         => $endTest,
            'is_recurring'     => 0,
            'is_cancelled'     => 0,
            'is_active'        => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        if (Schema::hasColumn('emploi_du_temps', 'course_element_id')) {
            $edtTestRow['course_element_id'] = $testCourse->id;
        }

        DB::table('emploi_du_temps')->insert($edtTestRow);

        // ── RÉSUMÉ ────────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('Résumé :');
        $this->command->info('  • ' . DB::table('academic_years')->count() . ' années académiques');
        $this->command->info('  • ' . DB::table('departments')->count()    . ' filières');
        $this->command->info('  • ' . DB::table('students')->count()       . ' étudiants');
        $this->command->info('  • ' . DB::table('attendances')->count()    . ' présences (présent / retard / absent)');
        $this->command->info('  • ' . DB::table('emploi_du_temps')->count() . ' créneaux emploi du temps');
        $this->command->info('');
        $this->command->info('Créneau test AUJOURD\'HUI : ' . $today . ' de ' . substr($startTest, 0, 5) . ' à ' . substr($endTest, 0, 5));
        $this->command->info('Dashboard, Management, Fingerprint, CourseAttendance prêts !');
    }
}
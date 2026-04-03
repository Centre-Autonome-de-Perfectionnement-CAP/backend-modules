<?php



namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Carbon\Carbon;



class AttendanceTestSeeder extends Seeder

{

    public function run(): void

    {

        $this->command->info(" Génération des données Attendance corrigées...");



        // ================= CLEAN =================

        DB::table('attendances')->delete();

        DB::table('students')->delete();

        DB::table('course_elements')->delete();

        DB::table('rooms')->delete();

        DB::table('academic_years')->delete();

        DB::table('buildings')->delete();

        DB::table('teaching_units')->delete();

        DB::table('filieres')->delete();



        // ================= DATA =================

        $niveaux = ["L1", "L2", "L3"];

        $matieres = ["Mathématiques", "Physique", "Programmation", "Réseaux"];

        $rooms = ["Salle A", "Salle B", "Salle C"];



        // ================= FILIERES =================

        $filieres = [

            "Génie civil (Licence professionnelle)",

            "Génie électrique (Licence professionnelle)",

            "Géomètre topographe (Licence professionnelle)",

            "Production animale (Licence professionnelle)",

            "Production végétale (Licence professionnelle)",

            "Analyse biomédicale",

            "Nutrition, diététique et technologie alimentaire",

            "Hygiène et contrôle de qualité",

            "Maintenance industrielle",

            "Mécanique automobile", // ✅ nouvelle filière

            "Biohygiène et sécurité sanitaire", // ✅ nouvelle filière

        ];



        $filiereIds = [];

        foreach ($filieres as $index => $name) {

            DB::table('filieres')->updateOrInsert(

                ['code' => 'FIL' . ($index + 1)],

                [

                    'uuid'        => Str::uuid(),

                    'name'        => $name,

                    'description' => null,

                    'created_at'  => now(),

                    'updated_at'  => now(),

                ]

            );

            $filiereIds[] = DB::table('filieres')->where('code', 'FIL' . ($index + 1))->value('id');

        }



        // ================= ACADEMIC YEARS =================

        $yearIds = [];

        foreach ([2019,2020, 2021, 2022, 2023, 2024, 2025] as $y) { // ✅ garde 2020-2021 jusqu’à 2025-2026

            $yearIds[] = DB::table('academic_years')->insertGetId([

                'uuid' => Str::uuid(),

                'academic_year' => $y . '-' . ($y + 1),

                'year_start' => $y,

                'year_end' => $y + 1,

                'created_at' => now(),

                'updated_at' => now()

            ]);

        }



        // ================= BUILDINGS =================

        $buildingIds = [];

        foreach (["Bâtiment A", "Bâtiment B"] as $index => $name) {

            DB::table('buildings')->updateOrInsert(

                ['code' => 'BLD' . ($index + 1)],

                [

                    'uuid' => Str::uuid(),

                    'name' => $name,

                    'created_at' => now(),

                    'updated_at' => now()

                ]

            );

            $buildingIds[] = DB::table('buildings')->where('code', 'BLD' . ($index + 1))->value('id');

        }



        // ================= ROOMS =================

        $roomIds = [];

        foreach ($rooms as $index => $roomName) {

            DB::table('rooms')->updateOrInsert(

                ['code' => 'ROOM' . ($index + 1)],

                [

                    'uuid'        => Str::uuid(),

                    'name'        => $roomName,

                    'building_id' => $buildingIds[array_rand($buildingIds)],

                    'created_at'  => now(),

                    'updated_at'  => now(),

                ]

            );

            $roomIds[] = DB::table('rooms')->where('code', 'ROOM' . ($index + 1))->value('id');

        }



        // ================= TEACHING UNITS =================

        $teachingUnitIds = [];

        foreach (["UE Sciences", "UE Informatique"] as $index => $ue) {

            DB::table('teaching_units')->updateOrInsert(

                ['code' => 'TU' . ($index + 1)],

                [

                    'uuid' => Str::uuid(),

                    'name' => $ue,

                    'created_at' => now(),

                    'updated_at' => now()

                ]

            );

            $teachingUnitIds[] = DB::table('teaching_units')->where('code', 'TU' . ($index + 1))->value('id');

        }



        // ================= COURSE ELEMENTS =================

        $courseElementIds = [];

        foreach ($matieres as $index => $matiere) {

            DB::table('course_elements')->updateOrInsert(

                ['code' => 'CE' . ($index + 1)],

                [

                    'uuid' => Str::uuid(),

                    'name' => $matiere,

                    'teaching_unit_id' => $teachingUnitIds[array_rand($teachingUnitIds)],

                    'created_at' => now(),

                    'updated_at' => now()

                ]

            );

            $courseElementIds[] = DB::table('course_elements')->where('code', 'CE' . ($index + 1))->value('id');

        }



        // ================= STUDENTS =================

        $studentIds = [];

        foreach ($filiereIds as $filiereId) {

            foreach ($niveaux as $niveau) {

                foreach ($yearIds as $yearId) {

                    for ($i = 1; $i <= 3; $i++) { // 3 étudiants par filière/niveau/année

                        $studentIds[] = DB::table('students')->insertGetId([

                            'uuid' => Str::uuid(),

                            'student_id_number' => 'ET' . str_pad(count($studentIds)+1, 4, '0', STR_PAD_LEFT),

                            'first_name' => "Prenom" . count($studentIds),

                            'last_name' => "Nom" . count($studentIds),

                            'matricule' => "MAT" . count($studentIds),

                            'niveau' => $niveau,

                            'fingerprint_status' => rand(0,1),

                            'academic_year_id' => $yearId,

                            'filiere_id' => $filiereId,

                            'password' => bcrypt('password'),

                            'created_at' => now(),

                            'updated_at' => now()

                        ]);

                    }

                }

            }

        }



        // ================= ATTENDANCES =================

        foreach ($studentIds as $studentId) {

            for ($m = 1; $m <= 6; $m++) {

                for ($d = 1; $d <= 5; $d++) {

                    DB::table('attendances')->insert([

                        'student_id' => $studentId,

                        'course_element_id' => $courseElementIds[array_rand($courseElementIds)],

                        'room_id' => $roomIds[array_rand($roomIds)],

                        'status' => rand(0, 100) > 20 ? 'present' : 'absent',

                        'date' => Carbon::create(2025, $m, rand(1, 28)),

                        'created_at' => now(),

                        'updated_at' => now()

                    ]);

                }

            }

        }



        $this->command->info("✅ Seeder Attendance prêt avec 12 filières et les années 2019-2020,2020-2021, 2021-2022, 2022-2023, 2023-2024, 2024-2025 et 2025-2026 !");

    }

}
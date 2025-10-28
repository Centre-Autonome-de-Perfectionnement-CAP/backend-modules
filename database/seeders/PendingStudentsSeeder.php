<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\EntryLevel;
use App\Modules\Inscription\Models\EntryDiploma;
use Illuminate\Support\Facades\DB;

class PendingStudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🎓 Création des niveaux d'entrée et diplômes...\n";

        // Créer les niveaux d'entrée
        $entryLevels = [
            ['name' => 'Licence 1', 'code' => 'L1', 'description' => 'Première année de Licence'],
            ['name' => 'Licence 2', 'code' => 'L2', 'description' => 'Deuxième année de Licence'],
            ['name' => 'Licence 3', 'code' => 'L3', 'description' => 'Troisième année de Licence'],
            ['name' => 'Master 1', 'code' => 'M1', 'description' => 'Première année de Master'],
            ['name' => 'Master 2', 'code' => 'M2', 'description' => 'Deuxième année de Master'],
        ];

        foreach ($entryLevels as $level) {
            EntryLevel::updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }

        echo "   ✓ " . count($entryLevels) . " niveaux d'entrée créés\n";

        // Créer les diplômes d'entrée
        $entryDiplomas = [
            ['name' => 'Baccalauréat', 'code' => 'BAC', 'description' => 'Diplôme du baccalauréat'],
            ['name' => 'Licence', 'code' => 'LIC', 'description' => 'Diplôme de Licence'],
            ['name' => 'Master', 'code' => 'MST', 'description' => 'Diplôme de Master'],
            ['name' => 'BTS', 'code' => 'BTS', 'description' => 'Brevet de Technicien Supérieur'],
            ['name' => 'DUT', 'code' => 'DUT', 'description' => 'Diplôme Universitaire de Technologie'],
        ];

        foreach ($entryDiplomas as $diploma) {
            EntryDiploma::updateOrCreate(
                ['code' => $diploma['code']],
                $diploma
            );
        }

        echo "   ✓ " . count($entryDiplomas) . " diplômes d'entrée créés\n";

        // Récupérer les IDs
        $levelL1 = EntryLevel::where('code', 'L1')->first()->id;
        $levelL2 = EntryLevel::where('code', 'L2')->first()->id;
        $levelL3 = EntryLevel::where('code', 'L3')->first()->id;
        $levelM1 = EntryLevel::where('code', 'M1')->first()->id;
        $levelM2 = EntryLevel::where('code', 'M2')->first()->id;

        $diplomaBac = EntryDiploma::where('code', 'BAC')->first()->id;
        $diplomaLic = EntryDiploma::where('code', 'LIC')->first()->id;
        $diplomaMst = EntryDiploma::where('code', 'MST')->first()->id;
        $diplomaBts = EntryDiploma::where('code', 'BTS')->first()->id;

        echo "\n👥 Création des étudiants en attente...\n";

        // Données d'étudiants en attente
        $pendingStudents = [
            // Statut: pending
            [
                'first_name' => 'Kouadio',
                'last_name' => 'Konan',
                'email' => 'kouadio.konan@example.ci',
                'phone' => '+225 0707010101',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'pending',
                'submitted_at' => now()->subDays(5),
            ],
            [
                'first_name' => 'Fatou',
                'last_name' => 'Traoré',
                'email' => 'fatou.traore@example.ci',
                'phone' => '+225 0707020202',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'pending',
                'submitted_at' => now()->subDays(4),
            ],
            [
                'first_name' => 'Yao',
                'last_name' => 'N\'Guessan',
                'email' => 'yao.nguessan@example.ci',
                'phone' => '+225 0707030303',
                'entry_level_id' => $levelL2,
                'entry_diploma_id' => $diplomaBts,
                'status' => 'pending',
                'submitted_at' => now()->subDays(3),
            ],
            [
                'first_name' => 'Aminata',
                'last_name' => 'Coulibaly',
                'email' => 'aminata.coulibaly@example.ci',
                'phone' => '+225 0707040404',
                'entry_level_id' => $levelM1,
                'entry_diploma_id' => $diplomaLic,
                'status' => 'pending',
                'submitted_at' => now()->subDays(2),
            ],
            [
                'first_name' => 'Kouamé',
                'last_name' => 'Brou',
                'email' => 'kouame.brou@example.ci',
                'phone' => '+225 0707050505',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'pending',
                'submitted_at' => now()->subDays(1),
            ],

            // Statut: documents_submitted
            [
                'first_name' => 'Awa',
                'last_name' => 'Diallo',
                'email' => 'awa.diallo@example.ci',
                'phone' => '+225 0707060606',
                'entry_level_id' => $levelL3,
                'entry_diploma_id' => $diplomaBts,
                'status' => 'documents_submitted',
                'submitted_at' => now()->subDays(7),
            ],
            [
                'first_name' => 'Jean',
                'last_name' => 'Kouassi',
                'email' => 'jean.kouassi@example.ci',
                'phone' => '+225 0707070707',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'documents_submitted',
                'submitted_at' => now()->subDays(8),
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'Touré',
                'email' => 'marie.toure@example.ci',
                'phone' => '+225 0707080808',
                'entry_level_id' => $levelM1,
                'entry_diploma_id' => $diplomaLic,
                'status' => 'documents_submitted',
                'submitted_at' => now()->subDays(6),
            ],

            // Statut: approved
            [
                'first_name' => 'Ibrahim',
                'last_name' => 'Koné',
                'email' => 'ibrahim.kone@example.ci',
                'phone' => '+225 0707090909',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'approved',
                'submitted_at' => now()->subDays(15),
            ],
            [
                'first_name' => 'Aïcha',
                'last_name' => 'Sangaré',
                'email' => 'aicha.sangare@example.ci',
                'phone' => '+225 0707101010',
                'entry_level_id' => $levelL2,
                'entry_diploma_id' => $diplomaBts,
                'status' => 'approved',
                'submitted_at' => now()->subDays(14),
            ],
            [
                'first_name' => 'Mamadou',
                'last_name' => 'Diomandé',
                'email' => 'mamadou.diomande@example.ci',
                'phone' => '+225 0707111111',
                'entry_level_id' => $levelM1,
                'entry_diploma_id' => $diplomaLic,
                'status' => 'approved',
                'submitted_at' => now()->subDays(13),
            ],

            // Statut: rejected
            [
                'first_name' => 'Koffi',
                'last_name' => 'Yapi',
                'email' => 'koffi.yapi@example.ci',
                'phone' => '+225 0707121212',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'rejected',
                'submitted_at' => now()->subDays(20),
            ],
            [
                'first_name' => 'Salimata',
                'last_name' => 'Ouattara',
                'email' => 'salimata.ouattara@example.ci',
                'phone' => '+225 0707131313',
                'entry_level_id' => $levelM2,
                'entry_diploma_id' => $diplomaMst,
                'status' => 'rejected',
                'submitted_at' => now()->subDays(18),
            ],

            // Plus d'étudiants en attente
            [
                'first_name' => 'Adama',
                'last_name' => 'Bakayoko',
                'email' => 'adama.bakayoko@example.ci',
                'phone' => '+225 0707141414',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'pending',
                'submitted_at' => now()->subHours(12),
            ],
            [
                'first_name' => 'Mariam',
                'last_name' => 'Sylla',
                'email' => 'mariam.sylla@example.ci',
                'phone' => '+225 0707151515',
                'entry_level_id' => $levelL3,
                'entry_diploma_id' => $diplomaBts,
                'status' => 'pending',
                'submitted_at' => now()->subHours(8),
            ],
            [
                'first_name' => 'Boubacar',
                'last_name' => 'Cissé',
                'email' => 'boubacar.cisse@example.ci',
                'phone' => '+225 0707161616',
                'entry_level_id' => $levelM1,
                'entry_diploma_id' => $diplomaLic,
                'status' => 'documents_submitted',
                'submitted_at' => now()->subDays(9),
            ],
            [
                'first_name' => 'Nafissatou',
                'last_name' => 'Diabaté',
                'email' => 'nafissatou.diabate@example.ci',
                'phone' => '+225 0707171717',
                'entry_level_id' => $levelL2,
                'entry_diploma_id' => $diplomaBts,
                'status' => 'pending',
                'submitted_at' => now()->subHours(6),
            ],
            [
                'first_name' => 'Seydou',
                'last_name' => 'Camara',
                'email' => 'seydou.camara@example.ci',
                'phone' => '+225 0707181818',
                'entry_level_id' => $levelL1,
                'entry_diploma_id' => $diplomaBac,
                'status' => 'approved',
                'submitted_at' => now()->subDays(12),
            ],
            [
                'first_name' => 'Fatoumata',
                'last_name' => 'Dembélé',
                'email' => 'fatoumata.dembele@example.ci',
                'phone' => '+225 0707191919',
                'entry_level_id' => $levelM1,
                'entry_diploma_id' => $diplomaLic,
                'status' => 'pending',
                'submitted_at' => now()->subHours(4),
            ],
            [
                'first_name' => 'Lamine',
                'last_name' => 'Fofana',
                'email' => 'lamine.fofana@example.ci',
                'phone' => '+225 0707202020',
                'entry_level_id' => $levelL3,
                'entry_diploma_id' => $diplomaBts,
                'status' => 'documents_submitted',
                'submitted_at' => now()->subDays(10),
            ],
        ];

        $created = 0;
        foreach ($pendingStudents as $student) {
            PendingStudent::updateOrCreate(
                ['email' => $student['email']],
                $student
            );
            $created++;
        }

        echo "   ✓ $created étudiants en attente créés\n\n";

        // Afficher les statistiques
        $pending = PendingStudent::where('status', 'pending')->count();
        $submitted = PendingStudent::where('status', 'documents_submitted')->count();
        $approved = PendingStudent::where('status', 'approved')->count();
        $rejected = PendingStudent::where('status', 'rejected')->count();

        echo "📊 STATISTIQUES\n";
        echo "═══════════════════════════════════════\n";
        echo "   📝 En attente: $pending\n";
        echo "   📄 Documents soumis: $submitted\n";
        echo "   ✅ Approuvés: $approved\n";
        echo "   ❌ Rejetés: $rejected\n";
        echo "   📊 Total: " . ($pending + $submitted + $approved + $rejected) . "\n";
        echo "═══════════════════════════════════════\n\n";

        echo "✅ Seeder terminé avec succès!\n";
    }
}

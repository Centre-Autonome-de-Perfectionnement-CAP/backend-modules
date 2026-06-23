<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder des vrais étudiants EPAC 2025-2026
 * NE TOUCHE PAS aux présences ni aux empreintes.
 *
 * Corrections appliquées :
 *  - Téléphone DEGBESSOUN corrigé : '97115947' → '0197115947'
 *  - Téléphones vides stockés null (pas '') pour GT
 *  - Création automatique du département si absent
 *  - Résumé complet en fin d'exécution
 *
 * Pré-requis : academic_years contient '2025-2026'
 * Ordre d'exécution : RealStudentsSeeder PUIS RealTimetableSeeder
 */
class RealStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📥 Insertion des vrais étudiants EPAC 2025-2026...');

        $year = DB::table('academic_years')
            ->where('academic_year', '2025-2026')->first();

        if (!$year) {
            $this->command->error('❌ Année 2025-2026 introuvable. Lancez AttendanceSeeder d\'abord.');
            return;
        }
        $yearId = $year->id;

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('students')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info('✓ Table students vidée');

        // ════════════════════════════════════════════════════════════════════
        // FORMAT : [matricule, nom, prenom, phone, niveau]
        // phone = null si inconnu pour les GT
        // ════════════════════════════════════════════════════════════════════

        // ── GÉNIE CIVIL (GC) — L1 — 62 étudiants ─────────────────────────────
        $etudiantsGC = [
            ['20268282', 'MAGNONFINON',    'Sillas Renaldo',                   '0197938080', 'L1'],
            ['20263370', 'ANATO',          'Pierre Hugues',                    '0151507369', 'L1'],
            ['20262886', 'ZINSOU',         'Ingrid Jennifer',                  '0167717109', 'L1'],
            ['20265809', 'ATCHABI',        'Olouwakemi Diane',                 '0166139555', 'L1'],
            ['20262283', 'NOBIME',         'Désiré Patrick Gbènoukpo',         '0197167726', 'L1'],
            ['20262895', 'DANTODJI',       'Nestor',                           '0196503152', 'L1'],
            ['20264633', 'KPAOU',          'Zakiatou',                         '0197560574', 'L1'],
            ['20262893', 'MEGNANHOU',      'Fiacre',                           '0196813193', 'L1'],
            ['20267088', 'HOUDONOUGBO',    'Kpessou Marie Lourdes',            '0196611151', 'L1'],
            ['20262384', 'BOVIS',          'Léos Farel-Rey',                   '0143756785', 'L1'],
            ['20264716', 'VIAHOUNDE',      'Dieu-donné Adolphe',               '0156173146', 'L1'],
            ['20268709', 'AKODEA',         'Sèlonwan Christ-Pain Emmanuel',    '0191832838', 'L1'],
            ['20262521', 'DANSOU',         'Marc Joël',                        '0169743133', 'L1'],
            ['20269096', 'DEGBESSOUN',     'Togbédji Urbain Pelier',           '0197115947', 'L1'], // ✅ CORRIGÉ
            ['20262866', 'VALO',           'Oclissou Moulinatou',              '0197725861', 'L1'],
            ['20264034', 'YEKPOGNI',       'Zessou Marcel Alognikin',          '0167312571', 'L1'],
            ['20268660', 'SINHOU',         'Zinsou Landry',                    '0166135962', 'L1'],
            ['20265453', 'GBETOHO',        'Houéfa Olive',                     '0151224102', 'L1'],
            ['20268258', 'BALOGOUN',       'Akodejou Ayégba Jules',            '0167842264', 'L1'],
            ['20261892', 'CBAGUIDI',       'Sènankpon Artilios Wolfgang',      '0191832497', 'L1'],
            ['20260398', 'BABA-YAYA',      'Latifou',                          '0190252378', 'L1'],
            ['20262763', 'GNONHOUE',       'Patrice',                          '0197485524', 'L1'],
            ['20264541', 'ADJOKANNON',     'Moïse',                            '0159232816', 'L1'],
            ['20262771', 'LAHITAN',        'Tokpè Chancelle Huguette Anne',    '0161760741', 'L1'],
            ['20262220', 'BOKOSSA',        'Omonlele Orellia Congita',         '0196109843', 'L1'],
            ['20267070', 'ZOUNMENOU',      'Sédjro Jérémie',                   '0167293891', 'L1'],
            ['20266508', 'ADEHOSSI',       'Bignon Prudencia Vanessa',         '0190301728', 'L1'],
            ['20262069', 'AFFOGNON',       'Mariano',                          '0164094462', 'L1'],
            ['20268192', 'NOUHOUSSOU',     'Sophonie Hector Mahugnon',         '0196800024', 'L1'],
            ['20262954', 'BANOUM',         'Hervé Asa',                        '0162233066', 'L1'],
            ['20265866', 'SALAME',         'Oluwashadé Egundoyin Bernice',     '0162155565', 'L1'],
            ['20264023', 'AGNIDE',         'Imouyi-deen',                      '0196842540', 'L1'],
            ['20265897', 'SANDA',          'Nadjath',                          '0161299611', 'L1'],
            ['20264175', 'WORO AGOUDA',    'Mardyatou',                        '0196140696', 'L1'],
            ['20264740', 'ANJORIN',        'Achraf Arèmou Adéyèmi',            '0166804994', 'L1'],
            ['20268213', 'AHOUANDJINOU',   'Mahoutin Christel Ronaldo',        '0159497744', 'L1'],
            ['20261263', 'EZIN',           "Kèm'tche Martial",                 '0122241085', 'L1'],
            ['20265893', 'TABE',           'Cheikh Abdel Cadoc Orphe',         '0143635914', 'L1'],
            ['20267912', 'WOLLO',          'Hospice',                          '0197266092', 'L1'],
            ['20262622', 'DAGBO',          'Charles Prince',                   '0140401989', 'L1'],
            ['20266759', 'AGO',            'Abigaëlle Chancelle Jesugnon',     '0162777389', 'L1'],
            ['20266698', 'ALIDOU',         'Olatunbji Farhane',                '0153843665', 'L1'],
            ['20267083', 'AGOSSOU',        'Aboudou-Saliou',                   '0166869875', 'L1'],
            ['20266654', 'DOSSOU-YOVO',    'Kolawolé Junias',                  '0159998182', 'L1'],
            ['20263516', 'HOUNING',        'Lazare',                           '0166342960', 'L1'],
            ['20263731', 'TOKPASSI',       "Fidèle O'brien",                   '0197578233', 'L1'],
            ['20267047', 'OROU KOUSSOU',   'Sadack',                           '0196457566', 'L1'],
            ['20262039', 'AHOTON',         'Séwanlan Pierrot Mekiade',         '0161004179', 'L1'],
            ['20267022', 'AHOMONTIN',      'Florien Noukpo',                   '0197717304', 'L1'],
            ['20261438', 'KOUKPELEROUN',   'Oluwafemi Joseph',                 '0197707017', 'L1'],
            ['20262403', "SANT'ANNA",      'Ulrich',                           '0196748250', 'L1'],
            ['20261273', 'KINHOU',         'Narcisse',                         '0196258057', 'L1'],
            ['20266274', 'HONGNON',        'Kolawole Thibaut Arnel',           '0197011899', 'L1'],
            ['20261500', 'AZONNOUDOU',     'Sedani Prince Karel',              '0154658000', 'L1'],
            ['20261390', 'LALEYE',         'Marcellus Luc-obéron Mouléro',     '0166433912', 'L1'],
            ['20268401', 'SOGLO',          'Paterne Serge',                    '0169128288', 'L1'],
            ['20262988', 'AKOGNON',        'Alban H. André',                   '0161303564', 'L1'],
            ['20263950', 'AHOWINOU',       'Assogba Hervé',                    '0141641294', 'L1'],
            ['20267055', 'AIKPE SOGAN',    'Hervé Clément',                    '0197122427', 'L1'],
            ['20260025', 'HOUSSOU GAGO',   'Ruchama Fallone Mahoussi',         '0197928897', 'L1'],
            ['20265198', 'COOVI',          'Mahouna Houenoumadji Kevin',       '0195802859', 'L1'],
            ['20261276', 'COUNOU',         'Lafia Waliou',                     '0197140125', 'L1'],
            ['20265174', 'OGA',            'Tchekan Wifried',                  '0197328020', 'L1'],
        ];

        // ── GÉNIE ÉLECTRIQUE (GE) — L1 — 29 étudiants ───────────────────────
        $etudiantsGE = [
            ['66414158', 'OBA',            'Chaffa Monboladji Marcellin',          '0166414158', 'L1'],
            ['20263273', 'ALLADASSI',      'Bignon Audrey',                        '0162414870', 'L1'],
            ['20263070', 'DOMINGO',        'Jaïrus Yaran Jean-Baptiste',           '0166996219', 'L1'],
            ['66219114', 'WINSOU',         'Fréjus Bénide',                        '0166219114', 'L1'],
            ['96357963', 'AGOSSANHADÉ',    'Eric',                                 '0196777966', 'L1'],
            ['70703601', 'ZAKPE',          'Parfait Jocelyn',                      '0160703601', 'L1'],
            ['97220474', 'GNANSOUNOU',     'Henagnon Charbel',                     '0197220474', 'L1'],
            ['47374318', 'COMANDA',        'Ruben',                                '0147374318', 'L1'],
            ['58551050', 'HOUNSOU',        'Finagnon Eric',                        '0196772735', 'L1'],
            ['20268288', 'AGBAMAHOU',      'Carmel',                               '0195887621', 'L1'],
            ['20265049', 'ESSOU',          'Kafui Emmanuela Floronique',           '0194006270', 'L1'],
            ['20261427', 'DOHOU',          'Djidé Serge',                          '0196253864', 'L1'],
            ['20269652', 'GNONLONFOUN',    'Prince',                               '0196123014', 'L1'],
            ['20264344', 'YENOU',          'Ségbègnon Bertran',                    '0166151996', 'L1'],
            ['20260405', 'HOUEKPODOGNI',   'Mawenan Arnaud',                       '0167921840', 'L1'],
            ['20262179', 'ADJOVI',         'Edmond',                               '0167528571', 'L1'],
            ['20269241', 'AMOUZOUN',       'Geoffroy',                             '0161211163', 'L1'],
            ['20261327', 'ELEGBEDE WOROU', 'Espedit',                              '0165129555', 'L1'],
            ['20269568', 'YADONCE LOKO',   'Michinel Bernard Mauriac',             '0161946213', 'L1'],
            ['20264952', 'TCHIKE',         'Narcisse Santos',                      '0167701564', 'L1'],
            ['20266043', 'KOUAGBA',        'Mahougnon Jérémie Epiphane',           '0187313711', 'L1'],
            ['20269849', 'TONONGBE',       'Madoché',                              '0166336067', 'L1'],
            ['20260153', 'OSSAH',          'Sotoria Jésugnon Bénédicte',           '0197994293', 'L1'],
            ['20261415', 'CUEDEGBE',       'Sêwêdo Atanda Kenneth Dosthrane',      '0197595260', 'L1'],
            ['20266927', 'SEWAGNOUIN',     'Patrice',                              '0161224768', 'L1'],
            ['20269980', 'DEGBE',          'Credo Lionel',                         '0196842038', 'L1'],
            ['20265990', 'OGATCHOROUN',    'Daré Honoré Toundé Leader',            '0161549827', 'L1'],
            ['60232STI24', 'TOHOUN',           'Elisabeth',                            '0196641753', 'L1'],
            ['60232STI25', 'BIGUEZOTON',       'Mahougnon',                            '0157866357', 'L1'],
        ];

        // ── GÉNIE MÉCANIQUE ET ÉNERGÉTIQUE (GME) — L1 — 15 étudiants ────────
        $etudiantsGME = [
            ['20266115', 'GOGOHUNGA',                   'Marc',                              '0161071227', 'L1'],
            ['20269977', 'ADJINAN',                     'Sèyivè Franck',                     '0196758936', 'L1'],
            ['20263199', 'AMOUSSOUGBO',                 'Ifédèlè Jean Ebénézer',             '0151226967', 'L1'],
            ['20267005', 'DE SOUZA',                    'Elpidio Randy Coovi',               '0196725847', 'L1'],
            ['20264123', 'DEGAN',                       "Tatao Orcy Tobi N'bonuto",          '0169993788', 'L1'],
            ['20265023', 'OROU ZIME',                   'Abdou-gamal',                       '0164926140', 'L1'],
            ['20268186', 'TOHOUNGODO ADANMANDOUGBENOU', 'Stanislas José Djromawuton',        '0164339082', 'L1'],
            ['20265258', 'AHOUANSOU',                   'Anselme Carin Wanignon',            '0143136028', 'L1'],
            ['20261078', 'EGAH',                        'David Muller',                      '0166515806', 'L1'],
            ['20268647', 'AKPADJA',                     'Rudy Veran Yaovi',                  '0199166515', 'L1'],
            ['20260326', 'DJAHO',                       'Mawugnon Dios Grazias Gratiano',    '0196660875', 'L1'],
            ['20262541', 'ILOUKOSSI',                   'Gustave',                           '0162778051', 'L1'],
            ['20263514', 'AGOÏ',                        'José-Marie Mechak Don-de-Dieu',     '0167198103', 'L1'],
            ['20263858', 'DANSOU',                      'Mahougnon Marcaire',                '0167449625', 'L1'],
            ['20268239', 'KOUDJINOU MALE',              'Bienvenu Abel',                     '0167249754', 'L1'],
        ];

        // ── GÉOMÈTRE TOPOGRAPHE (GT) — L1 — 57 étudiants ────────────────────
        // ✅ CORRIGÉ : phone = null (pas '') pour les numéros inconnus
        $etudiantsGT = [
            ['97332617', 'ADANGUIDI',            'Christlove Zorobabel',              null, 'L1'],
            ['62255788', 'ADANMITONDE',          'Houénagnon Daniel',                 null, 'L1'],
            ['62329063', 'ADANTINNON',           'Arsène',                            null, 'L1'],
            ['96517457', 'ADJAKOSSA',            'Ricardo Djaouvi',                   null, 'L1'],
            ['99688380', 'ADUAYOM',              'Collin Kévin',                      null, 'L1'],
            ['65310354', 'AGLOSSI',              'Corinne Mawugnon',                  null, 'L1'],
            ['20263348', 'AGO DEGBEMABOU',       'Maxime',                            null, 'L1'],
            ['66374981', 'AGOSSOU',              'Aubierge Anne',                     null, 'L1'],
            ['67063265', 'AHITONOU',             'Enadjèa Carnégy',                   null, 'L1'],
            ['94590986', 'AHO GLELE',            'Togninou Jéreck Paterne',           null, 'L1'],
            ['18223718', 'AKAMBI',               'Abdoul Chakour Babatounde Folahan', null, 'L1'],
            ['51123051', 'AKINOCHO',             'Jawad Olatogny Ichola',             null, 'L1'],
            ['69384444', 'ALINGO',               'Monia Philippe Modukpè',            null, 'L1'],
            ['69929248', 'ALISSOU',              'Agossou Koffi Jean de Dieu Helios', null, 'L1'],
            ['20261602', 'ASSIHLENON',           'Allihonou Fabrice',                 null, 'L1'],
            ['97629889', 'ASSOGBA',              'Victorin Wanignon',                 null, 'L1'],
            ['60788635', 'ATEGUI-BATCHO',        'Sourou Brice Clévice',              null, 'L1'],
            ['52220804', 'AYOSSOU',              'Mahouclo Batimé',                   null, 'L1'],
            ['97161736', 'BABARIMISSA',          'Fadel Abd-El Rachidi',              null, 'L1'],
            ['91949691', 'BADAROU',              'Abdou Rafik Adékpédjou',            null, 'L1'],
            ['20265056', 'BRUN',                 'Emeth Esdras',                      null, 'L1'],
            ['67870674', 'CIAHODE',              'Alida Rosette Cica',                null, 'L1'],
            ['10715912', 'DEGBEY',               'Seth',                              null, 'L1'],
            ['63042701', 'DJIKPESSE',            'Aurelien Crépin',                   null, 'L1'],
            ['66743402', 'DOSSOUMI',             'Narcisse',                          null, 'L1'],
            ['94526687', 'FADONOUGBO',           'Monyévèdo Régis Fortuné',           null, 'L1'],
            ['69037080', 'GANDO',                'Mahouna Symplice',                  null, 'L1'],
            ['10930219', 'GBAGUIDI',             'Magbondji Paterne Evrard',          null, 'L1'],
            ['67466203', 'GBEKPE',               'Sègbédé Moriac Hugue',              null, 'L1'],
            ['20265150', 'GNANCADJA',            'Josée Espérancia Gracia',           null, 'L1'],
            ['10250822', 'GNIMAGNON',            'Tanguy Tertius',                    null, 'L1'],
            ['65140982', 'GOUGBEMON',            'Tanguy Comlan Geoffroy',            null, 'L1'],
            ['62565491', 'HESSA',                'Sènansé Pertinie',                  null, 'L1'],
            ['99264672', 'HINNILO QUENUM',       'Geoffroy',                          null, 'L1'],
            ['21421425', 'HOUNNOU',              'Josué Mahouna',                     null, 'L1'],
            ['40897783', 'IDRISS SEYDINA',       'Oumar',                             null, 'L1'],
            ['69596789', 'KAGBOA',               'Adjimon Jéchonias Ezéchias',        null, 'L1'],
            ['67016959', 'KIANG-BENI',           'Aristhode Cédric',                  null, 'L1'],
            ['97870551', 'KONDE',                'Enagnon Mathieu',                   null, 'L1'],
            ['20538618', 'KOTTIN',               'Jules',                             null, 'L1'],
            ['96533428', 'KOUATONOU',            'Houétèyin Ghislain',                null, 'L1'],
            ['61972922', 'KOUHINKPO',            'Zinsou Spirito Santo',              null, 'L1'],
            ['20260963', 'KPATCHIA',             "Pawes'Pana Abdoul'Hafiz",           null, 'L1'],
            ['96522393', 'KPETCHEKOU',           'Dossou Fabien',                     null, 'L1'],
            ['96130901', 'LANHOUGBE',            'Cossi Arnaud',                      null, 'L1'],
            ['96562601', 'LEMON',                'Riyade Dine Emelin',                null, 'L1'],
            ['61131351', 'MAHOUNON',             'Chapdelain Carlos',                 null, 'L1'],
            ['67289509', 'MISSEKPE',             'Basilas',                           null, 'L1'],
            ['97608971', 'NOUHOUMON',            'Yédénou Michel',                    null, 'L1'],
            ['20261548', 'SEDEDJAN',             'Ferdinand',                         null, 'L1'],
            ['20263603', 'SEMEVO',               'Sèdami Irisda Josoué',              null, 'L1'],
            ['97546824', 'SINSIN',               'Janvier',                           null, 'L1'],
            ['20264213', 'SOHOU',                'Romaric',                           null, 'L1'],
            ['61665518', 'TENONKPONTO GBOSSEDE', 'Akomagnon Urbain',                  null, 'L1'],
            ['66818206', 'TOFFODJI',             'Jean Sèmassa',                      null, 'L1'],
            ['61771293', 'TOVIGBEDE',            'Houénagnon Armed',                  null, 'L1'],
            ['61517682', 'VIWATONOU',            'Alexandre',                         null, 'L1'],
            ['97813940', 'VODEME',               'Dalmas Franck',                     null, 'L1'],
        ];

        // ════════════════════════════════════════════════════════════════════
        //  INSERTION PAR FILIÈRE
        // ════════════════════════════════════════════════════════════════════
        $filieresData = [
            ['abbr' => 'GC',  'etudiants' => $etudiantsGC,  'label' => 'Génie Civil'],
            ['abbr' => 'GE',  'etudiants' => $etudiantsGE,  'label' => 'Génie Électrique'],
            ['abbr' => 'GME', 'etudiants' => $etudiantsGME, 'label' => 'Génie Mécanique et Énergétique'],
            ['abbr' => 'GT',  'etudiants' => $etudiantsGT,  'label' => 'Géomètre Topographe'],
        ];

        $inserted = 0;
        $errors   = 0;

        foreach ($filieresData as $fil) {
            $dept = DB::table('departments')->where('abbreviation', $fil['abbr'])->first();

            if (!$dept) {
                $this->command->warn("⚠️  Filière {$fil['abbr']} introuvable — création automatique...");
                $deptId = DB::table('departments')->insertGetId([
                    'uuid'         => Str::uuid(),
                    'name'         => $fil['label'],
                    'abbreviation' => $fil['abbr'],
                    'is_active'    => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            } else {
                $deptId = $dept->id;
            }

            foreach ($fil['etudiants'] as [$mat, $nom, $prenom, $phone, $niveau]) {
                // ✅ CORRIGÉ : null si vide, pas chaîne vide
                $phoneClean = null;
                if ($phone !== null && $phone !== '') {
                    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
                    $phoneClean = (strlen($cleaned) >= 6) ? $cleaned : $phone;
                }

                try {
                    DB::table('students')->insert([
                        'uuid'               => Str::uuid(),
                        'student_id_number'  => strtoupper($mat),
                        'password'           => Hash::make('password'),
                        'first_name'         => trim($prenom),
                        'last_name'          => trim($nom),
                        'matricule'          => strtoupper($mat),
                        'phone'              => $phoneClean,
                        'niveau'             => $niveau,
                        'filiere_id'         => $deptId,
                        'academic_year_id'   => $yearId,
                        'fingerprint_status' => 0,
                        'fingerprint_index'  => null,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                    $inserted++;
                } catch (\Exception $e) {
                    $this->command->warn("⚠️  Erreur pour {$mat} : " . $e->getMessage());
                    $errors++;
                }
            }

            $count = count($fil['etudiants']);
            $this->command->info("  ✓ {$fil['abbr']} ({$fil['label']}) : {$count} étudiants");
        }

        // ── Résumé ────────────────────────────────────────────────────────────
        $total = DB::table('students')->count();
        $this->command->info('');
        $this->command->info('📊 Résumé final :');
        $this->command->info("  • {$inserted} étudiants insérés avec succès");
        if ($errors > 0) $this->command->warn("  • {$errors} erreur(s)");
        $this->command->info("  • Total en BDD : {$total} étudiants");
        $this->command->info('  • fingerprint_status = 0 (Non enrôlé) pour tous');
        $this->command->info('  • fingerprint_index  = null pour tous');
        $this->command->info('');
        $this->command->info('✅ Prêt ! Lancez ensuite : php artisan db:seed --class=RealTimetableSeeder');
    }
}

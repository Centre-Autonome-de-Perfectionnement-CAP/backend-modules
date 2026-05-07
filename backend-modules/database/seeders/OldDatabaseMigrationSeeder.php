<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Inscription\Models\Cycle;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\EntryDiploma;
use App\Modules\Inscription\Models\AcademicYear;
use Carbon\Carbon;

class OldDatabaseMigrationSeeder extends Seeder
{
    private $sqlFile;
    private $idMapping = [
        'cycles' => [],
        'departments' => [],
        'diplomas' => [],
        'academic_years' => [],
    ];

    public function __construct()
    {
        $this->sqlFile = database_path('../u374405408_progiciel_cap (2).sql');
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Début de la migration depuis l\'ancienne base de données...');
        
        // Vérifier que le fichier SQL existe
        if (!file_exists($this->sqlFile)) {
            $this->command->error('❌ Fichier SQL introuvable: ' . $this->sqlFile);
            return;
        }

        // Migration des données de référence
        $this->migrateCycles();
        $this->migrateEntryDiplomas();
        $this->migrateAcademicYears();
        $this->migrateDepartments();
        
        $this->command->info('✅ Migration des données de référence terminée!');
        $this->command->info('📊 Statistiques:');
        $this->command->info('   - Cycles: ' . count($this->idMapping['cycles']));
        $this->command->info('   - Diplômes: ' . count($this->idMapping['diplomas']));
        $this->command->info('   - Départements: ' . count($this->idMapping['departments']));
        $this->command->info('   - Années académiques: ' . count($this->idMapping['academic_years']));
    }

    /**
     * Migrer les cycles depuis l'ancienne DB
     */
    private function migrateCycles(): void
    {
        $this->command->info('📚 Migration des cycles...');
        
        // Cycles de l'ancienne base de données
        $oldCycles = [
            ['id' => 7, 'nom' => 'Licence Professionnelle', 'nombre_annees' => 4, 'sigle' => 'DLP', 'lmd' => 1],
            ['id' => 8, 'nom' => 'Master Professionnel', 'nombre_annees' => 2, 'sigle' => 'DMP', 'lmd' => 1],
            ['id' => 9, 'nom' => 'Ingénierie', 'nombre_annees' => 4, 'sigle' => 'DIC', 'lmd' => 0],
        ];

        foreach ($oldCycles as $oldCycle) {
            $cycle = Cycle::updateOrCreate(
                ['name' => $oldCycle['nom']],
                [
                    'libelle' => $oldCycle['nom'],
                    'abbreviation' => $oldCycle['sigle'],
                    'years_count' => $oldCycle['nombre_annees'],
                    'is_lmd' => (bool) $oldCycle['lmd'],
                    'type' => strtolower($oldCycle['sigle']),
                    'is_active' => true,
                ]
            );

            $this->idMapping['cycles'][$oldCycle['id']] = $cycle->id;
            $this->command->info('   ✓ Cycle: ' . $oldCycle['nom'] . ' (ID: ' . $oldCycle['id'] . ' → ' . $cycle->id . ')');
        }
    }

    /**
     * Migrer les diplômes d'entrée
     */
    private function migrateEntryDiplomas(): void
    {
        $this->command->info('🎓 Migration des diplômes d\'entrée...');
        
        // Diplômes de l'ancienne DB (basé sur l'ID 1 le plus fréquent)
        $oldDiplomas = [
            ['id' => 1, 'nom' => 'Baccalauréat ou Équivalent'],
            ['id' => 2, 'nom' => 'BTS/DUT'],
            ['id' => 3, 'nom' => 'Licence'],
            ['id' => 4, 'nom' => 'Autre'],
        ];

        foreach ($oldDiplomas as $oldDiploma) {
            $diploma = EntryDiploma::updateOrCreate(
                ['name' => $oldDiploma['nom']],
                [
                    'abbreviation' => $oldDiploma['id'] == 1 ? 'BAC' : null,
                ]
            );

            $this->idMapping['diplomas'][$oldDiploma['id']] = $diploma->id;
            $this->command->info('   ✓ Diplôme: ' . $oldDiploma['nom'] . ' (ID: ' . $oldDiploma['id'] . ' → ' . $diploma->id . ')');
        }
    }

    /**
     * Migrer les années académiques (2002 à 2026)
     */
    private function migrateAcademicYears(): void
    {
        $this->command->info('📅 Migration des années académiques...');
        
        // Créer toutes les années de 2002 à 2026
        for ($year = 2002; $year <= 2026; $year++) {
            $academicYearString = $year . '-' . ($year + 1);
            
            $academicYear = AcademicYear::updateOrCreate(
                ['academic_year' => $academicYearString],
                [
                    'libelle' => 'Année Académique ' . $academicYearString,
                    'year_start' => Carbon::create($year, 10, 1),
                    'year_end' => Carbon::create($year + 1, 6, 30),
                    'submission_start' => Carbon::create($year, 8, 1),
                    'submission_end' => Carbon::create($year, 9, 30),
                    'reclamation_start' => Carbon::create($year + 1, 7, 1),
                    'reclamation_end' => Carbon::create($year + 1, 7, 31),
                    'is_current' => $year == date('Y'),
                ]
            );

            $this->idMapping['academic_years'][$year] = $academicYear->id;
        }
        
        $this->command->info('   ✓ ' . count($this->idMapping['academic_years']) . ' années académiques créées');
    }

    /**
     * Migrer les départements (filières)
     */
    private function migrateDepartments(): void
    {
        $this->command->info('🏢 Migration des départements...');
        
        // Filières de l'ancienne base de données
        $oldDepartments = [
            ['id' => 27, 'nom' => 'Génie Civil', 'cycle_id' => 7, 'sigle' => 'GC'],
            ['id' => 28, 'nom' => 'Génie Electrique', 'cycle_id' => 7, 'sigle' => 'GE'],
            ['id' => 29, 'nom' => 'Géomètre Topographe', 'cycle_id' => 7, 'sigle' => 'GT'],
            ['id' => 30, 'nom' => 'Production Animale', 'cycle_id' => 7, 'sigle' => 'PA'],
            ['id' => 31, 'nom' => 'Production Végétale', 'cycle_id' => 7, 'sigle' => 'PV'],
            ['id' => 32, 'nom' => 'Génie de l\'Environnement', 'cycle_id' => 7, 'sigle' => 'Gen'],
            ['id' => 33, 'nom' => 'Hygiène et Contrôle de Qualité', 'cycle_id' => 7, 'sigle' => 'HCQ'],
            ['id' => 34, 'nom' => 'Biohygiène et Sécurité Sanitaire', 'cycle_id' => 7, 'sigle' => 'BSS'],
            ['id' => 35, 'nom' => 'Analyses Biomédicales', 'cycle_id' => 7, 'sigle' => 'ABM'],
            ['id' => 36, 'nom' => 'Nutrition, Diététique et Technologie Alimentaire', 'cycle_id' => 7, 'sigle' => 'NDTA'],
            ['id' => 37, 'nom' => 'Génie Rural', 'cycle_id' => 7, 'sigle' => 'GR'],
            ['id' => 38, 'nom' => 'Maintenance Industrielle', 'cycle_id' => 7, 'sigle' => 'MI'],
            ['id' => 39, 'nom' => 'Mécanique Automobile', 'cycle_id' => 7, 'sigle' => 'MA'],
            ['id' => 40, 'nom' => 'Hydraulique', 'cycle_id' => 7, 'sigle' => 'HYD'],
            ['id' => 41, 'nom' => 'Fabrication Mécanique', 'cycle_id' => 7, 'sigle' => 'FM'],
            ['id' => 42, 'nom' => 'Froid et Climatisation', 'cycle_id' => 7, 'sigle' => 'FC'],
            ['id' => 43, 'nom' => 'Production Végétale et Post-Récolte', 'cycle_id' => 8, 'sigle' => 'PVPR'],
            ['id' => 44, 'nom' => 'Génie Civil', 'cycle_id' => 9, 'sigle' => 'GC'],
            ['id' => 45, 'nom' => 'Géomètre Topographe', 'cycle_id' => 9, 'sigle' => 'GT'],
            ['id' => 46, 'nom' => 'Génie Electrique', 'cycle_id' => 9, 'sigle' => 'GE'],
            ['id' => 47, 'nom' => 'Génie Mécanique et Energétique', 'cycle_id' => 7, 'sigle' => 'GME'],
            ['id' => 48, 'nom' => 'Génie Mécanique et Productique', 'cycle_id' => 7, 'sigle' => 'GMP'],
        ];

        foreach ($oldDepartments as $oldDept) {
            $newCycleId = $this->idMapping['cycles'][$oldDept['cycle_id']] ?? null;
            
            if (!$newCycleId) {
                $this->command->warn('   ⚠ Cycle introuvable pour le département: ' . $oldDept['nom']);
                continue;
            }

            $department = Department::updateOrCreate(
                [
                    'name' => $oldDept['nom'],
                    'cycle_id' => $newCycleId,
                    'abbreviation' => $oldDept['sigle']
                ],
                [
                    'description' => 'Formation en ' . $oldDept['nom'],
                    'is_active' => true,
                ]
            );

            $this->idMapping['departments'][$oldDept['id']] = $department->id;
            $this->command->info('   ✓ Département: ' . $oldDept['nom'] . ' (ID: ' . $oldDept['id'] . ' → ' . $department->id . ')');
        }
    }

    /**
     * Obtenir le mapping des IDs
     */
    public function getIdMapping(): array
    {
        return $this->idMapping;
    }
}

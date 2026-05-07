<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Cycle;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $licenceCycle = Cycle::where('name', 'LIKE', '%Licence%')->first();
        $masterCycle = Cycle::where('name', 'LIKE', '%Master%')->first();
        $ingenieurCycle = Cycle::where('name', 'LIKE', '%Ing%')->first();

        $departments = [
            // ==================== Départements Licence (cycle_id = 1) ====================
            ['name' => 'Génie Civil', 'abbreviation' => 'GC', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en construction et génie civil', 'is_active' => true],
            ['name' => 'Génie Electrique', 'abbreviation' => 'GE', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en électricité et électronique', 'is_active' => true],
            ['name' => 'Géomètre Topographe', 'abbreviation' => 'GT', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en topographie et cartographie', 'is_active' => true],
            ['name' => 'Production Animale', 'abbreviation' => 'PA', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en élevage et production animale', 'is_active' => true],
            ['name' => 'Production Végétale', 'abbreviation' => 'PV', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en agriculture et production végétale', 'is_active' => true],
            ['name' => 'Génie de l\'Environnement', 'abbreviation' => 'Gen', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en environnement et développement durable', 'is_active' => true],
            ['name' => 'Hygiène et Contrôle de Qualité', 'abbreviation' => 'HCQ', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en hygiène et contrôle qualité', 'is_active' => true],
            ['name' => 'Biohygiène et Sécurité Sanitaire', 'abbreviation' => 'BSS', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en biohygiène et sécurité', 'is_active' => true],
            ['name' => 'Analyses Biomédicales', 'abbreviation' => 'ABM', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en analyses médicales', 'is_active' => true],
            ['name' => 'Nutrition, Diététique et Technologie Alimentaire', 'abbreviation' => 'NDTA', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en nutrition et diététique', 'is_active' => true],
            ['name' => 'Génie Rural', 'abbreviation' => 'GR', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en génie rural et agricole', 'is_active' => true],
            ['name' => 'Maintenance Industrielle', 'abbreviation' => 'MI', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en maintenance des équipements industriels', 'is_active' => true],
            ['name' => 'Mécanique Automobile', 'abbreviation' => 'MA', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en mécanique automobile', 'is_active' => true],
            ['name' => 'Hydraulique', 'abbreviation' => 'HYD', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en hydraulique et réseaux', 'is_active' => true],
            ['name' => 'Fabrication Mécanique', 'abbreviation' => 'FM', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en fabrication mécanique', 'is_active' => true],
            ['name' => 'Froid et Climatisation', 'abbreviation' => 'FC', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en froid industriel et climatisation', 'is_active' => true],
            ['name' => 'Génie Mécanique et Energétique', 'abbreviation' => 'GME', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en mécanique et énergétique', 'is_active' => true],
            ['name' => 'Génie Mécanique et Productique', 'abbreviation' => 'GMP', 'cycle_id' => $licenceCycle?->id, 'description' => 'Formation en mécanique et productique', 'is_active' => true],

            // ==================== Départements Master (cycle_id = 2) ====================
            ['name' => 'Production Végétale et Post-Récolte', 'abbreviation' => 'PVPR', 'cycle_id' => $masterCycle?->id, 'description' => 'Master en production végétale et conservation', 'is_active' => true],

            // ==================== Cycle Ingénieur (cycle_id = 3) ====================
            // Spécialités d'ingénieur (3 dernières années)
            ['name' => 'Génie Civil', 'abbreviation' => 'GC', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Ingénierie en construction et ouvrages', 'is_active' => true],
            ['name' => 'Géomètre Topographe', 'abbreviation' => 'GT', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Ingénierie en topographie et géomatique', 'is_active' => true],
            ['name' => 'Génie Electrique', 'abbreviation' => 'GE', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Ingénierie électrique et électronique', 'is_active' => true],
            ['name' => 'Génie Mécanique et Energétique', 'abbreviation' => 'GME', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Ingénierie mécanique et énergétique', 'is_active' => true],
            
            // Classes préparatoires (2 premières années du cycle ingénieur)
            ['name' => 'Prépa Génie Civil', 'abbreviation' => 'P-GC', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Classes préparatoires pour Génie Civil (2 ans)', 'is_active' => true],
            ['name' => 'Prépa Géomètre Topographe', 'abbreviation' => 'P-GT', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Classes préparatoires pour Géomètre Topographe (2 ans)', 'is_active' => true],
            ['name' => 'Prépa Génie Electrique', 'abbreviation' => 'P-GE', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Classes préparatoires pour Génie Electrique (2 ans)', 'is_active' => true],
            ['name' => 'Prépa Génie Mécanique et Energétique', 'abbreviation' => 'P-GME', 'cycle_id' => $ingenieurCycle?->id, 'description' => 'Classes préparatoires pour GME (2 ans)', 'is_active' => true],
        ];

        foreach ($departments as $deptData) {
            Department::updateOrCreate(
                ['name' => $deptData['name'], 'cycle_id' => $deptData['cycle_id']],
                $deptData
            );
        }

        $this->command->info('✅ Departments créés avec succès!');
        $this->command->info('   - ' . count(array_filter($departments, fn($d) => $d['cycle_id'] === $licenceCycle?->id)) . ' départements Licence');
        $this->command->info('   - ' . count(array_filter($departments, fn($d) => $d['cycle_id'] === $masterCycle?->id)) . ' département Master');
        $this->command->info('   - ' . count(array_filter($departments, fn($d) => $d['cycle_id'] === $ingenieurCycle?->id)) . ' départements Ingénieur (Prépa + Spécialité)');
    }
}

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
        $licenceCycle = Cycle::where('name', 'Licence')->first();
        $masterCycle = Cycle::where('name', 'Master')->first();
        $ingenieurCycle = Cycle::where('name', 'Ingénieur')->first();

        $departments = [
            // Départements Licence
            [
                'name' => 'Génie Informatique',
                'abbreviation' => 'GI-L',
                'cycle_id' => $licenceCycle?->id,
                'description' => 'Formation en développement logiciel et systèmes',
                'is_active' => true,
            ],
            [
                'name' => 'Génie Électrique',
                'abbreviation' => 'GE-L',
                'cycle_id' => $licenceCycle?->id,
                'description' => 'Formation en électronique et automatisme',
                'is_active' => true,
            ],
            
            // Départements Master
            [
                'name' => 'Génie Logiciel',
                'abbreviation' => 'GL-M',
                'cycle_id' => $masterCycle?->id,
                'description' => 'Spécialisation en architecture et développement logiciel',
                'is_active' => true,
            ],
            [
                'name' => 'Systèmes Embarqués',
                'abbreviation' => 'SE-M',
                'cycle_id' => $masterCycle?->id,
                'description' => 'Spécialisation en systèmes temps réel et IoT',
                'is_active' => true,
            ],
            
            // Départements Ingénieur
            [
                'name' => 'Ingénierie Informatique',
                'abbreviation' => 'II-I',
                'cycle_id' => $ingenieurCycle?->id,
                'description' => 'Formation complète d\'ingénieur en informatique',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $deptData) {
            Department::updateOrCreate(
                ['name' => $deptData['name']],
                $deptData
            );
        }

        $this->command->info('✅ Departments créés avec succès!');
    }
}

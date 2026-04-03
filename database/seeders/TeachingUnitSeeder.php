<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Cours\Models\TeachingUnit;
use App\Modules\Cours\Models\Program;
use Illuminate\Support\Str;

class TeachingUnitSeeder extends Seeder
{
    public function run(): void
    {
        $programs = Program::take(10)->get();
        
        if ($programs->isEmpty()) {
            $this->command->warn('⚠️  Aucun programme trouvé. Exécutez ProgramSeeder d\'abord.');
            return;
        }

        $teachingUnits = [
            // UE communes
            ['name' => 'Mathématiques', 'code' => 'MATH', 'credits' => 6, 'coefficient' => 3],
            ['name' => 'Physique', 'code' => 'PHYS', 'credits' => 4, 'coefficient' => 2],
            ['name' => 'Anglais', 'code' => 'ANG', 'credits' => 2, 'coefficient' => 1],
            ['name' => 'Communication', 'code' => 'COM', 'credits' => 2, 'coefficient' => 1],
            
            // UE Informatique
            ['name' => 'Programmation', 'code' => 'PROG', 'credits' => 6, 'coefficient' => 3],
            ['name' => 'Algorithmique', 'code' => 'ALGO', 'credits' => 6, 'coefficient' => 3],
            ['name' => 'Base de données', 'code' => 'BDD', 'credits' => 5, 'coefficient' => 2.5],
            ['name' => 'Réseaux', 'code' => 'RES', 'credits' => 5, 'coefficient' => 2.5],
            ['name' => 'Systèmes d\'exploitation', 'code' => 'SE', 'credits' => 4, 'coefficient' => 2],
            ['name' => 'Génie logiciel', 'code' => 'GL', 'credits' => 6, 'coefficient' => 3],
            
            // UE Électronique
            ['name' => 'Électronique analogique', 'code' => 'EANA', 'credits' => 6, 'coefficient' => 3],
            ['name' => 'Électronique numérique', 'code' => 'ENUM', 'credits' => 6, 'coefficient' => 3],
            ['name' => 'Automatisme', 'code' => 'AUTO', 'credits' => 5, 'coefficient' => 2.5],
            ['name' => 'Microcontrôleurs', 'code' => 'MICRO', 'credits' => 5, 'coefficient' => 2.5],
        ];

        $created = 0;
        foreach ($programs as $program) {
            // Sélectionner 4-6 UE aléatoires par programme
            $selectedUEs = collect($teachingUnits)->random(rand(4, 6));
            
            foreach ($selectedUEs as $ueData) {
                TeachingUnit::updateOrCreate(
                    [
                        'code' => $ueData['code'] . '-' . $program->code,
                        'program_id' => $program->id,
                    ],
                    [
                        'uuid' => Str::uuid()->toString(),
                        'name' => $ueData['name'],
                        'credits' => $ueData['credits'],
                        'coefficient' => $ueData['coefficient'],
                        'description' => "Unité d'enseignement {$ueData['name']} pour {$program->name}",
                        'is_mandatory' => rand(0, 1) == 1,
                    ]
                );
                $created++;
            }
        }

        $this->command->info('✅ Unités d\'enseignement créées avec succès!');
        $this->command->info("📖 Total: {$created} UE créées");
    }
}

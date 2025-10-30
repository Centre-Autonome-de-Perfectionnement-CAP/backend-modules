<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\Cycle;
use Illuminate\Support\Str;

class CycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cycles = [
            [
                'name' => 'Licence',
                'libelle' => 'Licence Professionnelle',
                'abbreviation' => 'L',
                'years_count' => 3,
                'is_lmd' => true,
                'type' => 'licence',
                'description' => 'Cycle de Licence Professionnelle (3 ans)',
                'is_active' => true,
            ],
            [
                'name' => 'Master',
                'libelle' => 'Master Professionnel',
                'abbreviation' => 'M',
                'years_count' => 2,
                'is_lmd' => true,
                'type' => 'master',
                'description' => 'Cycle de Master Professionnel (2 ans)',
                'is_active' => true,
            ],
            [
                'name' => 'Ingénieur',
                'libelle' => 'Cycle d\'Ingénieur',
                'abbreviation' => 'I',
                'years_count' => 5,
                'is_lmd' => false,
                'type' => 'ingenieur',
                'description' => 'Formation d\'Ingénieur (5 ans) - Inclut Prépa (2 ans) + Spécialité (3 ans)',
                'is_active' => true,
            ],
        ];

        foreach ($cycles as $cycleData) {
            Cycle::updateOrCreate(
                ['name' => $cycleData['name']],
                $cycleData
            );
        }

        $this->command->info('✅ Cycles créés avec succès!');
    }
}

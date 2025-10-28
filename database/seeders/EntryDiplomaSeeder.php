<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Inscription\Models\EntryDiploma;
use Illuminate\Support\Str;

class EntryDiplomaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diplomas = [
            // Diplômes pour accès Licence
            [
                'name' => 'Baccalauréat Scientifique',
                'code' => 'BAC_S',
                'level' => 'secondary',
                'description' => 'Diplôme du baccalauréat série scientifique',
            ],
            [
                'name' => 'BTS',
                'code' => 'BTS',
                'level' => 'post_secondary',
                'description' => 'Brevet de Technicien Supérieur',
            ],
            [
                'name' => 'DUT',
                'code' => 'DUT',
                'level' => 'post_secondary',
                'description' => 'Diplôme Universitaire de Technologie',
            ],
            [
                'name' => 'DTI',
                'code' => 'DTI',
                'level' => 'post_secondary',
                'description' => 'Diplôme de Technicien Industriel',
            ],
            [
                'name' => 'DEAT',
                'code' => 'DEAT',
                'level' => 'post_secondary',
                'description' => 'Diplôme d\'Études Appliquées et Technologiques',
            ],
            
            // Diplômes pour accès Master
            [
                'name' => 'Licence Professionnelle',
                'code' => 'LP',
                'level' => 'bachelor',
                'description' => 'Diplôme de Licence Professionnelle',
            ],
            [
                'name' => 'Licence Académique',
                'code' => 'LA',
                'level' => 'bachelor',
                'description' => 'Diplôme de Licence Académique',
            ],
            
            // Diplômes pour accès Ingénieur
            [
                'name' => 'Certificat de Classes Préparatoires',
                'code' => 'PREPA',
                'level' => 'preparatory',
                'description' => 'Certificat de réussite des classes préparatoires',
            ],
        ];

        foreach ($diplomas as $diplomaData) {
            EntryDiploma::updateOrCreate(
                ['code' => $diplomaData['code']],
                array_merge($diplomaData, [
                    'uuid' => Str::uuid()->toString(),
                ])
            );
        }

        $this->command->info('✅ Entry Diplomas créés avec succès!');
    }
}

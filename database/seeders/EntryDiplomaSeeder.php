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
                'abbreviation' => 'BAC S',
                'entry_level' => 'Licence 1',
            ],
            [
                'name' => 'BTS',
                'abbreviation' => 'BTS',
                'entry_level' => 'Licence 3',
            ],
            [
                'name' => 'DUT',
                'abbreviation' => 'DUT',
                'entry_level' => 'Licence 3',
            ],
            [
                'name' => 'DTI',
                'abbreviation' => 'DTI',
                'entry_level' => 'Licence 3',
            ],
            [
                'name' => 'DEAT',
                'abbreviation' => 'DEAT',
                'entry_level' => 'Licence 3',
            ],
            
            // Diplômes pour accès Master
            [
                'name' => 'Licence Professionnelle',
                'abbreviation' => 'LP',
                'entry_level' => 'Master 1',
            ],
            [
                'name' => 'Licence Académique',
                'abbreviation' => 'LA',
                'entry_level' => 'Master 1',
            ],
            
            // Diplômes pour accès Ingénieur
            [
                'name' => 'Certificat de Classes Préparatoires',
                'abbreviation' => 'PREPA',
                'entry_level' => 'Ingénieur 1',
            ],
        ];

        foreach ($diplomas as $diplomaData) {
            EntryDiploma::updateOrCreate(
                ['name' => $diplomaData['name']],
                $diplomaData
            );
        }

        $this->command->info('✅ Entry Diplomas créés avec succès!');
    }
}

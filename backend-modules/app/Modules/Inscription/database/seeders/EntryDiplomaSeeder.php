<?php

namespace App\Modules\Inscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EntryDiplomaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diplomas = [
            ['name' => 'Baccalauréat Scientifique'],
            ['name' => 'BTS (Brevet de Technicien Supérieur)'],
            ['name' => 'DUT (Diplôme Universitaire de Technologie)'],
            ['name' => 'Licence Professionnelle'],
            ['name' => 'Master 1'],
            ['name' => 'Master 2'],
            ['name' => 'Diplôme d\'Ingénieur'],
            ['name' => 'Certificat Prépa Ingénieur'],
        ];

        foreach ($diplomas as $diploma) {
            DB::table('entry_diplomas')->updateOrInsert(
                ['name' => $diploma['name']],
                [
                    'name' => $diploma['name'],
                    'uuid' => Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Entry diplomas seeded successfully!');
    }
}

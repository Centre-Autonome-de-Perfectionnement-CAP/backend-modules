<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\RH\Models\Professor;
use Illuminate\Support\Str;

class ProfessorSeeder extends Seeder
{
    public function run(): void
    {
        $professors = [
            [
                'first_name' => 'Jean',
                'last_name' => 'KOUASSI',
                'email' => 'j.kouassi@cap.edu',
                'phone' => '+225 07 00 00 01',
                'speciality' => 'Informatique',
                'status' => 'active',
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'KONE',
                'email' => 'm.kone@cap.edu',
                'phone' => '+225 07 00 00 02',
                'speciality' => 'Mathématiques',
                'status' => 'active',
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'YAO',
                'email' => 'p.yao@cap.edu',
                'phone' => '+225 07 00 00 03',
                'speciality' => 'Électronique',
                'status' => 'active',
            ],
            [
                'first_name' => 'Fatou',
                'last_name' => 'TRAORE',
                'email' => 'f.traore@cap.edu',
                'phone' => '+225 07 00 00 04',
                'speciality' => 'Physique',
                'status' => 'active',
            ],
            [
                'first_name' => 'Amadou',
                'last_name' => 'BAMBA',
                'email' => 'a.bamba@cap.edu',
                'phone' => '+225 07 00 00 05',
                'speciality' => 'Génie Civil',
                'status' => 'active',
            ],
        ];

        foreach ($professors as $profData) {
            Professor::updateOrCreate(
                ['email' => $profData['email']],
                array_merge($profData, [
                    'uuid' => Str::uuid()->toString(),
                ])
            );
        }

        $this->command->info('✅ Professeurs créés avec succès!');
    }
}

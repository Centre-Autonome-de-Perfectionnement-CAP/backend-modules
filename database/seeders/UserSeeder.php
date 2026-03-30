<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Modules\Stockage\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'last_name' => 'ADMIN',
                'first_name' => 'Chef CAP',
                'email' => 'chef@cap.edu',
                'password' => Hash::make('password123'),
                'phone' => '+229 97 00 00 01',
                'rib_number' => '4151710001123',
                'role' => 'chef-cap',
            ],
            [
                'last_name' => 'KOUASSI',
                'first_name' => 'Jean',
                'email' => 'chef.division@cap.edu',
                'password' => Hash::make('password123'),
                'phone' => '+229 97 00 00 02',
                'rib_number' => '4151710001123',
                'role' => 'chef-division',
            ],
            [
                'last_name' => 'ADJOVI',
                'first_name' => 'Marie',
                'email' => 'secretaire@cap.edu',
                'password' => Hash::make('password123'),
                'phone' => '+229 97 00 00 03',
                'rib_number' => '7807528193',
                'role' => 'secretaire',
            ],
            [
                'last_name' => 'HOUNGBO',
                'first_name' => 'Paul',
                'email' => 'comptable@cap.edu',
                'password' => Hash::make('password123'),
                'phone' => '+229 97 00 00 04',
                'rib_number' => '0918625822',
                'role' => 'comptable',
            ],
            [
                'last_name' => 'GBAGUIDI',
                'first_name' => 'Luc',
                'email' => 'support@cap.edu',
                'password' => Hash::make('password123'),
                'phone' => '+229 97 00 00 05',
                'rib_number' => '15171881891',
                'role' => 'soutien-informatique',
            ],
            [
                'last_name' => 'DOSSOU',
                'first_name' => 'Sophie',
                'email' => 'professeur@cap.edu',
                'password' => Hash::make('password123'),
                'phone' => '+229 97 00 00 06',
                'rib_number' => '191981561672',
                'role' => 'professeur',
            ],
            [
                'last_name' => 'ETUDIANT',
                'first_name' => 'Test',
                'email' => 'etudiant@cap.edu',
                'password' => Hash::make('password123'),
                'phone' => '+229 97 00 00 07',
                'rib_number' => '191981561672',
                'role' => 'etudiant',
            ],
        ];

        foreach ($users as $userData) {
            $roleName = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'uuid' => Str::uuid()->toString(),
                    'email_verified_at' => now(),
                ])
            );

            // Vérification du rôle
            $role = Role::where('slug', $roleName)->first();

            if (!$role) {
                $this->command->error("Rôle introuvable : $roleName");
                continue;
            }

            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        $this->command->info('Utilisateurs créés avec succès!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Stockage\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les rôles
        $chefCapRole = Role::where('name', 'chef_cap')->first();
        $chefDivisionRole = Role::where('name', 'chef_division')->first();
        $secretaireRole = Role::where('name', 'secretaire')->first();
        $comptableRole = Role::where('name', 'comptable')->first();

        // Créer les utilisateurs administratifs
        $administrations = [
            [
                'last_name' => 'SANYA',
                'first_name' => 'Max',
                'email' => 'owomax@gmail.com',
                'phone' => '61332652',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!')),
                'rib_number' => '',
                'rib' => '',
                'photo' => NULL,
                'ifu_number' => '',
                'ifu' => '',
                'bank' => '',
            ],
            [
                'last_name' => 'DOSSOU',
                'first_name' => 'Florence A.',
                'email' => 'fldossou@gmail.com',
                'phone' => '96855759',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!')),
                'rib_number' => '',
                'rib' => '',
                'photo' => NULL,
                'ifu_number' => '',
                'ifu' => '',
                'bank' => '',
            ],
            [
                'last_name' => 'TCHOBO',
                'first_name' => 'Fidèle Paul',
                'email' => 'fideletchobo@gmail.com',
                'phone' => '97686201',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!')),
                'rib_number' => '',
                'rib' => '',
                'photo' => NULL,
                'ifu_number' => '',
                'ifu' => '',
                'bank' => '',
            ],
            [
                'last_name' => 'AHOUNOU',
                'first_name' => 'Serge',
                'email' => 'ahounouserge@gmail.com',
                'phone' => '97011862',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!')),
                'rib_number' => '',
                'rib' => '',
                'photo' => NULL,
                'ifu_number' => '',
                'ifu' => '',
                'bank' => '',
            ],
            [
                'last_name' => 'ZANNOU',
                'first_name' => 'Julienne',
                'email' => 'zahoju22@gmail.com',
                'phone' => '97589187',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!')),
                'rib_number' => '',
                'rib' => '',
                'photo' => NULL,
                'ifu_number' => '',
                'ifu' => '',
                'bank' => '',
            ],
        ];

        $roles = [
            'owomax@gmail.com' => $chefDivisionRole,
            'fldossou@gmail.com' => $comptableRole,
            'fideletchobo@gmail.com' => $chefCapRole,
            'ahounouserge@gmail.com' => $chefDivisionRole,
            'zahoju22@gmail.com' => $secretaireRole,
        ];

        foreach ($administrations as $admin) {
            $user = User::firstOrCreate(
                ['email' => $admin['email']],
                $admin
            );

            // Assigner le rôle via la table pivot
            if (isset($roles[$admin['email']])) {
                $user->roles()->syncWithoutDetaching([$roles[$admin['email']]->id]);
            }
        }
    }
}

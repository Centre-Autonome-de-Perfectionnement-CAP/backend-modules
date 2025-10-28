<?php

namespace Database\Seeders;

use App\Modules\Stockage\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'chef_cap',
                'display_name' => 'Chef CAP',
                'description' => 'Chef du Centre d\'Apprentissage Professionnel',
                'is_system' => true,
            ],
            [
                'name' => 'chef_division',
                'display_name' => 'Chef de Division',
                'description' => 'Chef de division administrative',
                'is_system' => true,
            ],
            [
                'name' => 'chef_division_continue',
                'display_name' => 'Chef Division Formation Continue',
                'description' => 'Chef de la division Formation Continue (Ingénierie et Master)',
                'is_system' => true,
            ],
            [
                'name' => 'chef_division_distance',
                'display_name' => 'Chef Division Formation à Distance',
                'description' => 'Chef de la division Formation à Distance (Licence)',
                'is_system' => true,
            ],
            [
                'name' => 'comptable',
                'display_name' => 'Comptable',
                'description' => 'Responsable de la comptabilité',
                'is_system' => true,
            ],
            [
                'name' => 'secretaire',
                'display_name' => 'Secrétaire',
                'description' => 'Secrétaire administrative',
                'is_system' => true,
            ],
            [
                'name' => 'etudiant',
                'display_name' => 'Étudiant',
                'description' => 'Étudiant inscrit',
                'is_system' => true,
            ],
            [
                'name' => 'responsable',
                'display_name' => 'Responsable',
                'description' => 'Responsable pédagogique',
                'is_system' => true,
            ],
            [
                'name' => 'soutien_informatique',
                'display_name' => 'Soutien Informatique',
                'description' => 'Membre du support technique et développement',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}

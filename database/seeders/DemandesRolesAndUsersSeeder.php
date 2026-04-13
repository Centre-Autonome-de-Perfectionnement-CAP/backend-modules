<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder pour les nouveaux rôles et utilisateurs du module Demandes.
 *
 * NOUVEAUX RÔLES créés :
 *  - secretaire-da          : Secrétaire de la Directrice Adjointe
 *  - directrice-adjointe    : Directrice Adjointe
 *  - secretaire-directeur   : Secrétaire du Directeur
 *
 * RÔLE EXISTANT DUPLIQUÉ :
 *  - chef-division : créé 2 users distincts avec chef_division_type différent
 *
 * COMMANDE : php artisan db:seed --class=DemandesRolesAndUsersSeeder
 */
class DemandesRolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Créer les nouveaux rôles ───────────────────────────────────────
        $newRoles = [
            [
                'name' => 'Secrétaire Direction Adjointe',
                'slug' => 'secretaire-da',
            ],
            [
                'name' => 'Directrice Adjointe',
                'slug' => 'directrice-adjointe',
            ],
            [
                'name' => 'Secrétaire du Directeur',
                'slug' => 'secretaire-directeur',
            ],
        ];

        foreach ($newRoles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                [
                    'uuid'       => Str::uuid(),
                    'name'       => $role['name'],
                    'slug'       => $role['slug'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // ── 2. Récupérer les IDs des rôles ────────────────────────────────────
        $roleIds = DB::table('roles')->pluck('id', 'slug');

        // ── 3. Créer les 2 utilisateurs Chef Division ─────────────────────────
        // (si le rôle chef-division existe déjà, on crée juste les 2 users)
        $chefDivisionRoleId = $roleIds['chef-division'] ?? null;

        if ($chefDivisionRoleId) {
            $chefDivUsers = [
                [
                    'last_name'          => 'CHEF DIVISION',
                    'first_name'         => 'Formation Distance',
                    'email'              => 'chef.division.distance@cap-epac.bj',
                    'chef_division_type' => 'formation_distance',
                    'role_slug'          => 'chef-division',
                ],
                [
                    'last_name'          => 'CHEF DIVISION',
                    'first_name'         => 'Formation Continue',
                    'email'              => 'chef.division.continue@cap-epac.bj',
                    'chef_division_type' => 'formation_continue',
                    'role_slug'          => 'chef-division',
                ],
            ];

            foreach ($chefDivUsers as $userData) {
                $existingUser = DB::table('users')->where('email', $userData['email'])->first();
                if (!$existingUser) {
                    $userId = DB::table('users')->insertGetId([
                        'uuid'                => Str::uuid(),
                        'last_name'           => $userData['last_name'],
                        'first_name'          => $userData['first_name'],
                        'email'               => $userData['email'],
                        'password'            => Hash::make('ChangeMe2024!'), // À CHANGER
                        'chef_division_type'  => $userData['chef_division_type'],
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                    // Attacher le rôle
                    DB::table('role_user')->insertOrIgnore([
                        'role_id'    => $chefDivisionRoleId,
                        'user_id'    => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // ── 4. Créer les utilisateurs pour les nouveaux rôles ─────────────────
        $newUsers = [
            [
                'last_name'  => 'SECRÉTAIRE',
                'first_name' => 'Direction Adjointe',
                'email'      => 'secretaire.da@cap-epac.bj',
                'role_slug'  => 'secretaire-da',
            ],
            [
                'last_name'  => 'DIRECTRICE',
                'first_name' => 'Adjointe',
                'email'      => 'directrice.adjointe@cap-epac.bj',
                'role_slug'  => 'directrice-adjointe',
            ],
            [
                'last_name'  => 'SECRÉTAIRE',
                'first_name' => 'Directeur',
                'email'      => 'secretaire.directeur@cap-epac.bj',
                'role_slug'  => 'secretaire-directeur',
            ],
        ];

        foreach ($newUsers as $userData) {
            $existingUser = DB::table('users')->where('email', $userData['email'])->first();
            if (!$existingUser) {
                $roleId = $roleIds[$userData['role_slug']] ?? null;
                if (!$roleId) continue;

                $userId = DB::table('users')->insertGetId([
                    'uuid'       => Str::uuid(),
                    'last_name'  => $userData['last_name'],
                    'first_name' => $userData['first_name'],
                    'email'      => $userData['email'],
                    'password'   => Hash::make('ChangeMe2024!'), // À CHANGER
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('role_user')->insertOrIgnore([
                    'role_id'    => $roleId,
                    'user_id'    => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ Rôles et utilisateurs du module Demandes créés avec succès.');
        $this->command->warn('⚠️  N\'oubliez pas de changer les mots de passe par défaut !');
        $this->command->table(
            ['Email', 'Rôle', 'Mot de passe par défaut'],
            array_map(fn($u) => [$u['email'], $u['role_slug'], 'ChangeMe2024!'], $newUsers)
        );
    }
}

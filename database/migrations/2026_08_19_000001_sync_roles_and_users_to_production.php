<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Synchronise les rôles et utilisateurs entre local et production.
     * - Ajoute chef_division_type à users si absente
     * - Renomme le slug chef-division → responsable-division
     * - Insère les rôles manquants (admin, sec-da, sec-dir)
     * - Assigne les bons rôles aux users existants
     * Idempotente : sans truncate, sans destruction de données existantes.
     */
    public function up(): void
    {
        // 1. Ajouter la colonne chef_division_type à users si elle n'existe pas
        if (!Schema::hasColumn('users', 'chef_division_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('chef_division_type', ['formation_distance', 'formation_continue'])
                      ->nullable()
                      ->after('id');
            });
        }

        // 2. Renommer le slug chef-division → responsable-division (id 3)
        DB::table('roles')
            ->where('slug', 'chef-division')
            ->update([
                'slug'       => 'responsable-division',
                'name'       => 'Responsable Division',
                'updated_at' => now(),
            ]);

        // 3. Insérer les rôles manquants (admin, sec-da, sec-dir)
        $missingRoles = [
            [
                'uuid'       => Str::uuid()->toString(),
                'name'       => 'Administrateur',
                'slug'       => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid'       => Str::uuid()->toString(),
                'name'       => 'Secrétaire Directrice Adjointe',
                'slug'       => 'sec-da',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid'       => Str::uuid()->toString(),
                'name'       => 'Secrétaire Directeur',
                'slug'       => 'sec-dir',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($missingRoles as $role) {
            $exists = DB::table('roles')->where('slug', $role['slug'])->exists();
            if (!$exists) {
                DB::table('roles')->insert($role);
            }
        }


    }

    public function down(): void
    {
        // Retour arrière : on remet chef-division (best effort)
        DB::table('roles')
            ->where('slug', 'responsable-division')
            ->where('id', 3)
            ->update([
                'slug'       => 'chef-division',
                'name'       => 'Chef de division',
                'updated_at' => now(),
            ]);

        // Supprimer les rôles ajoutés
        DB::table('roles')->whereIn('slug', ['admin', 'sec-da', 'sec-dir'])->delete();

        // Retirer la colonne chef_division_type si elle a été ajoutée
        if (Schema::hasColumn('users', 'chef_division_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('chef_division_type');
            });
        }
    }
};

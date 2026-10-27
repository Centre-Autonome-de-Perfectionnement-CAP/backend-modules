<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::table('roles')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [

    ["id" => 2, "uuid" => "2ec85d38-c91b-11f0-b164-f1f70328c04a", "name" => "Chef CAP", "slug" => "chef-cap", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 3, "uuid" => "2ec8606c-c91b-11f0-b164-f1f70328c04a", "name" => "Chef de division", "slug" => "chef-division", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 4, "uuid" => "2ec861a7-c91b-11f0-b164-f1f70328c04a", "name" => "Secrétaire", "slug" => "secretaire", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 5, "uuid" => "2ec86223-c91b-11f0-b164-f1f70328c04a", "name" => "Comptable", "slug" => "comptable", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 6, "uuid" => "2ec8628d-c91b-11f0-b164-f1f70328c04a", "name" => "Soutien informatique", "slug" => "soutien-informatique", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 7, "uuid" => "2ec8631f-c91b-11f0-b164-f1f70328c04a", "name" => "Professeur", "slug" => "professeur", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 8, "uuid" => "2ec8637a-c91b-11f0-b164-f1f70328c04a", "name" => "Étudiant", "slug" => "etudiant", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 9, "uuid" => "2ec863d6-c91b-11f0-b164-f1f70328c04a", "name" => "Responsable", "slug" => "responsable", "created_at" => "2025-11-24 09:51:43", "updated_at" => "2025-11-24 09:51:43", "deleted_at" => null],

    ["id" => 10, "uuid" => "27adc542-ce48-11f0-b164-f1f70328c04a", "name" => "Directeur", "slug" => "directeur", "created_at" => "2025-11-30 23:56:14", "updated_at" => "2025-11-30 23:56:14", "deleted_at" => null],

    ["id" => 11, "uuid" => "3ce6190f-ce48-11f0-b164-f1f70328c04a", "name" => "Directrice adjointe", "slug" => "directrice-adjointe", "created_at" => "2025-11-30 23:56:50", "updated_at" => "2025-11-30 23:56:50", "deleted_at" => null],

 ];
    }
}

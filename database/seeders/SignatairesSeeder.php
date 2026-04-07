<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SignatairesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('signataires')->truncate();
        DB::table('signataires')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "role_id" => 10, "nom" => "Dr (MC) Victorien Tamègnon DOUGNON", "created_at" => "2025-12-01 00:02:44", "updated_at" => "2025-12-02 18:54:11"],
            ["id" => 2, "role_id" => 2, "nom" => "Professeur Fidèle Paul TCHOBO", "created_at" => "2025-12-01 00:03:08", "updated_at" => "2025-12-02 18:54:26"],
        ];
    }
}

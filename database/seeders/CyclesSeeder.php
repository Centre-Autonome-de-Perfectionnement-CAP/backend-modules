<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CyclesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cycles')->truncate();
        DB::table('cycles')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "8676edb1-2cab-483d-80d2-83cb2ab93716", "name" => "Licence Professionnelle", "libelle" => "Licence Professionnelle", "abbreviation" => "DLP", "years_count" => 4, "is_lmd" => 1, "type" => "dlp", "description" => null, "is_active" => 1, "created_at" => "2025-11-03 13:18:36", "updated_at" => "2025-11-03 13:18:36", "deleted_at" => null],
            ["id" => 2, "uuid" => "434ecfc7-f4cd-43aa-8386-6e2016a2c35c", "name" => "Master Professionnel", "libelle" => "Master Professionnel", "abbreviation" => "DMP", "years_count" => 2, "is_lmd" => 1, "type" => "dmp", "description" => null, "is_active" => 1, "created_at" => "2025-11-03 13:18:36", "updated_at" => "2025-11-03 13:18:36", "deleted_at" => null],
            ["id" => 3, "uuid" => "32a9461b-78d6-4753-a5b2-09e3839e0edc", "name" => "Ingénierie", "libelle" => "Ingénierie", "abbreviation" => "DIC", "years_count" => 4, "is_lmd" => 0, "type" => "dic", "description" => null, "is_active" => 1, "created_at" => "2025-11-03 13:18:36", "updated_at" => "2025-11-03 13:18:36", "deleted_at" => null],
        ];
    }
}

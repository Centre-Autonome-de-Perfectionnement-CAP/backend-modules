<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportantInformationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('important_informations')->truncate();
        DB::table('important_informations')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 3, "title" => "Résultats CUO", "description" => "Résultats CUO", "icon" => "bi-info-circle", "color" => "primary", "link" => null, "file_id" => 1630, "is_active" => 0, "order" => 0, "created_at" => "2026-02-12 06:23:23", "updated_at" => "2026-02-12 06:50:47"],
        ];
    }
}

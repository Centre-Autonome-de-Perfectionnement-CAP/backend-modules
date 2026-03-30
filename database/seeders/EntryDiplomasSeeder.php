<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntryDiplomasSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('entry_diplomas')->truncate();
        DB::table('entry_diplomas')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "7b203b8c-1675-4fd4-86ad-d29d1f44729c", "name" => "Baccalauréat Scientifique", "abbreviation" => null, "entry_level" => null, "created_at" => "2025-11-03 13:18:28", "updated_at" => "2025-11-03 13:18:28", "deleted_at" => null],
            ["id" => 2, "uuid" => "95b89aac-a21a-4b72-b763-8151251ff919", "name" => "BTS", "abbreviation" => null, "entry_level" => null, "created_at" => "2025-11-03 13:18:28", "updated_at" => "2025-11-03 13:18:28", "deleted_at" => null],
            ["id" => 3, "uuid" => "802448cf-dfc1-4f93-b83b-46347418e99d", "name" => "DUT", "abbreviation" => null, "entry_level" => null, "created_at" => "2025-11-03 13:18:28", "updated_at" => "2025-11-03 13:18:28", "deleted_at" => null],
            ["id" => 4, "uuid" => "4cd46d1c-b3db-4239-87f7-8178656ba0df", "name" => "Licence Professionnelle", "abbreviation" => null, "entry_level" => null, "created_at" => "2025-11-03 13:18:28", "updated_at" => "2025-11-03 13:18:28", "deleted_at" => null],
            ["id" => 5, "uuid" => "fa3d816b-60ee-4595-a737-2156e8bb8650", "name" => "Master 1", "abbreviation" => null, "entry_level" => null, "created_at" => "2025-11-03 13:18:28", "updated_at" => "2025-11-03 13:18:28", "deleted_at" => null],
            ["id" => 6, "uuid" => "ba7e9e5f-a458-4e28-82ea-74f3fa41fa6f", "name" => "Master 2", "abbreviation" => null, "entry_level" => null, "created_at" => "2025-11-03 13:18:28", "updated_at" => "2025-11-03 13:18:28", "deleted_at" => null],
            ["id" => 7, "uuid" => "820765db-44c6-4a8d-a216-1165ac46df44", "name" => "Certificat Prépa Ingénieur", "abbreviation" => null, "entry_level" => null, "created_at" => "2025-11-03 13:18:28", "updated_at" => "2025-11-03 13:18:28", "deleted_at" => null],
            ["id" => 13, "uuid" => "", "name" => "DEAT", "abbreviation" => "DEAT", "entry_level" => "1", "created_at" => null, "updated_at" => null, "deleted_at" => null],
            ["id" => 15, "uuid" => "ba7e9e5f-a458-4e28-82ea-74f3fa41fa6v", "name" => "DTI", "abbreviation" => "DTI", "entry_level" => "1", "created_at" => null, "updated_at" => null, "deleted_at" => null],
        ];
    }
}

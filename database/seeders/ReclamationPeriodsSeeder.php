<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReclamationPeriodsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('reclamation_periods')->truncate();
        DB::table('reclamation_periods')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "f78f365d-df20-4721-a344-f4f01e9655e7", "academic_year_id" => 23, "department_id" => null, "start_date" => "2025-11-18", "end_date" => "2025-11-30", "is_active" => 1, "description" => null, "created_at" => "2025-11-18 19:07:22", "updated_at" => "2025-11-18 19:07:22", "deleted_at" => null],
            ["id" => 2, "uuid" => "5346f947-56ee-4583-a801-811723236c56", "academic_year_id" => 24, "department_id" => null, "start_date" => "2025-11-25", "end_date" => "2025-12-31", "is_active" => 1, "description" => null, "created_at" => "2025-11-25 15:40:11", "updated_at" => "2025-11-25 15:40:11", "deleted_at" => null],
            ["id" => 3, "uuid" => "71598f1c-b06a-47ef-a3cd-6264ec8a2665", "academic_year_id" => 23, "department_id" => null, "start_date" => "2025-11-25", "end_date" => "2025-11-26", "is_active" => 1, "description" => null, "created_at" => "2025-11-25 15:43:16", "updated_at" => "2025-11-25 15:43:16", "deleted_at" => null],
        ];
    }
}

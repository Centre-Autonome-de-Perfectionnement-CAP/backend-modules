<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassGroupsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('class_groups')->truncate();
        DB::table('class_groups')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "79d11f47-7f3a-4fd2-b096-d9f9297344e5", "academic_year_id" => 23, "department_id" => 24, "study_level" => "1", "validation_average" => 10.00, "group_name" => "A", "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-12-01 00:10:06"],
            ["id" => 2, "uuid" => "6f5a36b1-1277-44f3-a727-94faba684ac8", "academic_year_id" => 23, "department_id" => 25, "study_level" => "1", "validation_average" => 10.00, "group_name" => "A", "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-12-01 00:14:29"],
        ];
    }
}

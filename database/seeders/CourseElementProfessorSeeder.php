<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseElementProfessorSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('course_element_professor')->truncate();
        DB::table('course_element_professor')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 1, "professor_id" => 1, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:40:08", "updated_at" => "2025-11-26 12:40:08"],
            ["id" => 2, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 2, "professor_id" => 1, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:40:21", "updated_at" => "2025-11-26 12:40:21"],
            ["id" => 3, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 3, "professor_id" => 4, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:40:33", "updated_at" => "2025-11-26 12:40:33"],
            ["id" => 4, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 5, "professor_id" => 3, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:40:44", "updated_at" => "2025-11-26 12:40:44"],
            ["id" => 5, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 4, "professor_id" => 3, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:41:00", "updated_at" => "2025-11-26 12:41:00"],
            ["id" => 6, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 11, "professor_id" => 6, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:41:12", "updated_at" => "2025-11-26 12:41:12"],
            ["id" => 7, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 10, "professor_id" => 5, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:41:21", "updated_at" => "2025-11-26 12:41:21"],
            ["id" => 8, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 9, "professor_id" => 5, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:41:30", "updated_at" => "2025-11-26 12:41:30"],
            ["id" => 9, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 8, "professor_id" => 2, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:41:45", "updated_at" => "2025-11-26 12:41:45"],
            ["id" => 10, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 7, "professor_id" => 7, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:43:34", "updated_at" => "2025-11-26 12:43:34"],
            ["id" => 11, "academic_year_id" => null, "class_group_id" => null, "course_element_id" => 6, "professor_id" => 7, "principal_professor_id" => null, "is_primary" => 1, "created_at" => "2025-11-26 12:43:46", "updated_at" => "2025-11-26 12:43:46"],
        ];
    }
}

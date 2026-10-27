<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('programs')->truncate();
        DB::table('programs')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "5a78b6cd-60c8-4d93-8c19-ebff6c291876", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 1, "course_element_professor_id" => 1, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 13:59:57", "updated_at" => "2025-11-26 16:42:31"],
            ["id" => 2, "uuid" => "04526724-67c4-46ba-8a8d-c311572de9a9", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 1, "course_element_professor_id" => 2, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:00:09", "updated_at" => "2025-11-26 17:10:29"],
            ["id" => 3, "uuid" => "3f28a505-b3a4-4ba4-8696-4bda327ffe45", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 1, "course_element_professor_id" => 3, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:00:19", "updated_at" => "2025-11-26 17:38:51"],
            ["id" => 4, "uuid" => "e1c3b117-6553-4cd2-82c2-4b1a27d75b99", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 1, "course_element_professor_id" => 4, "weighting" => "[100]", "retake_weighting" => null, "created_at" => "2025-11-26 14:00:35", "updated_at" => "2025-11-26 16:58:23"],
            ["id" => 5, "uuid" => "a7c70981-4cb3-4ed3-b93a-67b1aaab0e2f", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 1, "course_element_professor_id" => 5, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:00:46", "updated_at" => "2025-11-26 17:01:27"],
            ["id" => 6, "uuid" => "4804f1b7-9a54-427f-85c1-537abddb05be", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 1, "course_element_professor_id" => 11, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:01:02", "updated_at" => "2025-11-27 12:16:27"],
            ["id" => 7, "uuid" => "9170d9b1-fdda-472c-8184-4d201b242af0", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 1, "course_element_professor_id" => 10, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:01:14", "updated_at" => "2025-11-26 17:28:26"],
            ["id" => 8, "uuid" => "cbe5c998-2da7-4447-b515-c4ca38f906fd", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 2, "course_element_professor_id" => 1, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:02:37", "updated_at" => "2025-11-26 17:00:41"],
            ["id" => 9, "uuid" => "dd84db0a-864e-4265-bace-31881027b693", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 2, "course_element_professor_id" => 2, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:02:48", "updated_at" => "2025-11-26 17:05:57"],
            ["id" => 10, "uuid" => "63063fdf-8e7c-4b6d-90b6-79ee33d071f2", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 2, "course_element_professor_id" => 3, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:02:59", "updated_at" => "2025-11-26 17:32:28"],
            ["id" => 11, "uuid" => "64571290-22b8-44da-8dc5-68a5753f7c3b", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 2, "course_element_professor_id" => 5, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:03:12", "updated_at" => "2025-11-26 17:14:32"],
            ["id" => 12, "uuid" => "55056eef-098b-4f5c-b569-bc305bab2ff2", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 2, "course_element_professor_id" => 4, "weighting" => "[100]", "retake_weighting" => null, "created_at" => "2025-11-26 14:03:26", "updated_at" => "2025-11-26 16:52:30"],
            ["id" => 13, "uuid" => "87209b75-5547-4631-8029-4ce6c1430533", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 2, "course_element_professor_id" => 11, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:03:41", "updated_at" => "2025-11-27 12:28:54"],
            ["id" => 14, "uuid" => "66df3dd9-cc47-4b3f-acfe-058897285b67", "academic_year_id" => 1, "semester" => 1, "class_group_id" => 2, "course_element_professor_id" => 10, "weighting" => "[50,50]", "retake_weighting" => null, "created_at" => "2025-11-26 14:03:53", "updated_at" => "2025-11-26 17:33:52"],
        ];
    }
}

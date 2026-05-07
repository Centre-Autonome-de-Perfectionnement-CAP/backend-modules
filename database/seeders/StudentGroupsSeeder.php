<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentGroupsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('student_groups')->truncate();
        DB::table('student_groups')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "baf2e215-83c2-49ab-a304-2e0a11de199a", "class_group_id" => 1, "student_id" => 3450, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 2, "uuid" => "bc846e6d-4fa0-4e99-a947-7340a9e9c1aa", "class_group_id" => 1, "student_id" => 3451, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 3, "uuid" => "e3915e9f-c0db-4b69-94ef-fe65a2576684", "class_group_id" => 1, "student_id" => 3452, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 4, "uuid" => "4d8e37f0-cf4c-413f-830f-5d2e6f8cbb1a", "class_group_id" => 1, "student_id" => 3453, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 5, "uuid" => "299238d3-4990-47ce-929c-4f3c3b009a55", "class_group_id" => 1, "student_id" => 3457, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 6, "uuid" => "2fa49ac8-a672-4c11-a583-0b318f7515b7", "class_group_id" => 1, "student_id" => 3461, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 7, "uuid" => "4884ed0a-2314-4181-8994-b41f76fc3799", "class_group_id" => 1, "student_id" => 3463, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 8, "uuid" => "bb615149-6164-4341-89ff-b124fc68bd28", "class_group_id" => 1, "student_id" => 3467, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 9, "uuid" => "863dc8f7-5fbb-4070-a4ed-abee559a1ee3", "class_group_id" => 1, "student_id" => 3469, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 10, "uuid" => "d31a2038-4858-453c-b945-3c51a031b609", "class_group_id" => 1, "student_id" => 3471, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 11, "uuid" => "7ea4bab0-a53a-4ca0-b67c-fa053ea8a2fc", "class_group_id" => 1, "student_id" => 3472, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 12, "uuid" => "2ab13b83-708a-4c44-b92c-72c11343fd28", "class_group_id" => 1, "student_id" => 3473, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 13, "uuid" => "5807d2ae-d03a-4464-9465-39f78c4d781d", "class_group_id" => 1, "student_id" => 3474, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 14, "uuid" => "9f58cdaa-6479-47f4-98df-bc0bc3aaa2be", "class_group_id" => 1, "student_id" => 3478, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 15, "uuid" => "6872064c-7d66-4507-8c06-2c500fb10d3a", "class_group_id" => 1, "student_id" => 3479, "created_at" => "2025-11-26 13:41:19", "updated_at" => "2025-11-26 13:41:19"],
            ["id" => 16, "uuid" => "c0e83bf4-3563-4837-95ec-1c05d59d804d", "class_group_id" => 2, "student_id" => 3445, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 17, "uuid" => "60befdde-f93e-4cb9-98f2-ef4b1c0ce6e9", "class_group_id" => 2, "student_id" => 3446, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 18, "uuid" => "38dc46ef-2a1a-40dd-aeca-c61cd2645a83", "class_group_id" => 2, "student_id" => 3447, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 19, "uuid" => "170f8061-18c1-408f-9ce3-b03d3849953d", "class_group_id" => 2, "student_id" => 3448, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 20, "uuid" => "77929270-50f1-4311-9a7a-94b3e37f8e00", "class_group_id" => 2, "student_id" => 3449, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 21, "uuid" => "3008d98a-1605-4d09-a5d8-8010101601ab", "class_group_id" => 2, "student_id" => 3454, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 22, "uuid" => "0120dc90-c6ce-4b80-8872-6e5dbffcd02c", "class_group_id" => 2, "student_id" => 3455, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 23, "uuid" => "7870e3b0-13ea-4556-a383-e8b38257c3c0", "class_group_id" => 2, "student_id" => 3456, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 24, "uuid" => "4167267e-765d-415b-8827-ec93dc5738db", "class_group_id" => 2, "student_id" => 3458, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 25, "uuid" => "4e671ea8-817f-4cf9-9eef-ad7060fe0a09", "class_group_id" => 2, "student_id" => 3459, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 26, "uuid" => "15829750-ad88-4c14-9988-db52ba048dca", "class_group_id" => 2, "student_id" => 3460, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 27, "uuid" => "f60280ec-9d44-45cb-b867-6eb288c4a2c3", "class_group_id" => 2, "student_id" => 3462, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 28, "uuid" => "a2e43e38-eeb7-4044-8e84-d3e7067f61fd", "class_group_id" => 2, "student_id" => 3475, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
            ["id" => 29, "uuid" => "36003fae-50d2-47aa-9083-8f8cda266645", "class_group_id" => 2, "student_id" => 3476, "created_at" => "2025-11-26 13:56:06", "updated_at" => "2025-11-26 13:56:06"],
        ];
    }
}

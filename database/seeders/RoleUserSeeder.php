<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_user')->truncate();
        DB::table('role_user')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 2, "role_id" => 3, "user_id" => 1, "created_at" => null, "updated_at" => null],
            ["id" => 3, "role_id" => 3, "user_id" => 3, "created_at" => null, "updated_at" => null],
            ["id" => 4, "role_id" => 3, "user_id" => 2, "created_at" => null, "updated_at" => null],
        ];
    }
}

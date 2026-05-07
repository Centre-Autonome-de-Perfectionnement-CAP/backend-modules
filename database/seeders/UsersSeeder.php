<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('users')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            [
                "id" => 1,
                "uuid" => "e7b3f4c2-9d6a-4a1e-8f2d-3b9f5c7a1e4b",
                "last_name" => "Administrateur",
                "first_name" => "Admin",
                "email" => "admin@cap-epac.online",
                "email_verified_at" => null,
                "password" => Hash::make('password123'),
                "phone" => "97654323",
                "rib_number" => null,
                "rib" => null,
                "photo" => null,
                "ifu_number" => null,
                "ifu" => null,
                "bank" => null,
                "remember_token" => null,
                "created_at" => null,
                "updated_at" => null,
                "deleted_at" => null
            ],

            [
                "id" => 2,
                "uuid" => "db3c2820-da67-4178-bd3a-f25945d1fc72",
                "last_name" => "SANYA",
                "first_name" => "Max Fréjus",
                "email" => "owomax@gmail.com",
                "email_verified_at" => null,
                "password" => Hash::make('password123'),
                "phone" => "61332652",
                "rib_number" => null,
                "rib" => null,
                "photo" => null,
                "ifu_number" => null,
                "ifu" => null,
                "bank" => null,
                "remember_token" => null,
                "created_at" => "2025-11-17 06:57:00",
                "updated_at" => "2025-11-17 06:57:00",
                "deleted_at" => null
            ],

            [
                "id" => 3,
                "uuid" => "13610b35-960e-4649-aabc-837f7f065363",
                "last_name" => "ZANNOU",
                "first_name" => "Julienne",
                "email" => "julienne@cap-epac.online",
                "email_verified_at" => null,
                "password" => Hash::make('password123'),
                "phone" => "52697138",
                "rib_number" => "123456789123456789123456",
                "rib" => null,
                "photo" => null,
                "ifu_number" => "1517191971012",
                "ifu" => null,
                "bank" => "Orabank",
                "remember_token" => null,
                "created_at" => "2025-12-01 00:05:01",
                "updated_at" => "2025-12-01 00:05:01",
                "deleted_at" => null
            ],
        ];
    }
}

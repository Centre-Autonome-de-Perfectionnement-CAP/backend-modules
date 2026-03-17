<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessorsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('professors')->truncate();
        DB::table('professors')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "9ece7d78-8c86-4f1e-97ab-942677401b3d", "first_name" => "Daniel", "last_name" => "SABI TAKOU", "email" => "daniel@gmail.com", "phone" => "52697137", "password" => '$2y$12$HLcZnymi/OkyWh4ZD9EsNefv3QzYq.RkPLnJR1K3msaEAuw9.FkCu', "rib_number" => "1234567891234567891234567", "rib" => null, "ifu_number" => "1517191971012", "ifu" => null, "bank" => "Orabank", "specialty" => null, "status" => "active", "grade_id" => 4, "role_id" => 6, "bio" => null, "created_at" => "2025-11-26 12:29:31", "updated_at" => "2025-11-26 12:29:31", "deleted_at" => null],

            ["id" => 2, "uuid" => "fcd96c14-80cc-44ba-ab37-eaa55566123c", "first_name" => "Jean", "last_name" => "KOUDI", "email" => "jean@gmail.com", "phone" => "52697138", "password" => '$2y$12$V1OAuv14VpnCbs9hnE1l3OG6RjIN5Pby.kMiDh7SpIQc7El2L3p1q', "rib_number" => "123456789123456789123456", "rib" => null, "ifu_number" => "1517191971012", "ifu" => null, "bank" => "Orabank", "specialty" => null, "status" => "active", "grade_id" => 4, "role_id" => 6, "bio" => null, "created_at" => "2025-11-26 12:30:29", "updated_at" => "2025-11-26 12:30:29", "deleted_at" => null],

            ["id" => 3, "uuid" => "6ecacc95-ab2a-4eac-8ddd-fcc6abf53beb", "first_name" => "Max Fréjus", "last_name" => "SANYA", "email" => "max@gmail.com", "phone" => "52697139", "password" => '$2y$12$AbjNPpnfnXsNVL58z3S3AevwPROOgIPDm74iOZhO7VBAtnyzsoXfO', "rib_number" => "123456789123456789123456", "rib" => null, "ifu_number" => "1517191971012", "ifu" => null, "bank" => "Orabank", "specialty" => null, "status" => "active", "grade_id" => 2, "role_id" => 6, "bio" => null, "created_at" => "2025-11-26 12:31:15", "updated_at" => "2025-11-26 12:31:15", "deleted_at" => null],

            ["id" => 4, "uuid" => "c48e3b7a-1f17-4eae-9044-34f63c2d6a2c", "first_name" => "Faras", "last_name" => "ISSIAKO", "email" => "faras@gmail.com", "phone" => "52697140", "password" => '$2y$12$noI3dfJTQcu2OORTnbIMOOdtxolGUXXW.SGTjBtxkgrjTFJEDDrdi', "rib_number" => "123456789123456789123456", "rib" => null, "ifu_number" => "1517191971012", "ifu" => null, "bank" => "Orabank", "specialty" => null, "status" => "active", "grade_id" => 4, "role_id" => 6, "bio" => null, "created_at" => "2025-11-26 12:32:03", "updated_at" => "2025-11-26 12:32:03", "deleted_at" => null],

            ["id" => 5, "uuid" => "3d7b98cd-3771-4501-ae7a-ee7ae4159b2e", "first_name" => "Macaire", "last_name" => "AGBOMAHENAN", "email" => "macaire@gmail.com", "phone" => "52697141", "password" => '$2y$12$tYwSMx9g6V.0xlj4ZQGc4ez/6u4cLcA7UqeG7gEkFD3WQeZCR2wQC', "rib_number" => "123456789123456789123456", "rib" => null, "ifu_number" => "1517191971012", "ifu" => null, "bank" => "Orabank", "specialty" => null, "status" => "active", "grade_id" => 1, "role_id" => 6, "bio" => null, "created_at" => "2025-11-26 12:32:49", "updated_at" => "2025-11-26 12:32:49", "deleted_at" => null],

            ["id" => 6, "uuid" => "300d0a48-37d9-4832-b4a3-05795aa41d02", "first_name" => "Renaud", "last_name" => "d'ALMEIDA", "email" => "renaud@gmail.com", "phone" => "52697142", "password" => '$2y$12$sY8g52Tk38tHFbHO/XMinuNCld6lvu3Z.ZUF/pOqF8IYLqZgeJZJq', "rib_number" => "123456789123456789123456", "rib" => null, "ifu_number" => "1517191971012", "ifu" => null, "bank" => "Orabank", "specialty" => null, "status" => "active", "grade_id" => 4, "role_id" => 6, "bio" => null, "created_at" => "2025-11-26 12:33:35", "updated_at" => "2025-11-26 12:33:35", "deleted_at" => null],

            ["id" => 7, "uuid" => "c2123764-7025-45b8-8152-95e7dc802816", "first_name" => "Marius", "last_name" => "BOCCO KOUBE", "email" => "marius@gmail.com", "phone" => "52697143", "password" => '$2y$12$RedizTgwyK0d.pSgfIkF4OdQqV7dpbzy1UstcDmRCmZBZ6sdjLF5W', "rib_number" => "123456789123456789123456", "rib" => null, "ifu_number" => "1517191971012", "ifu" => null, "bank" => "Orabank", "specialty" => null, "status" => "active", "grade_id" => 4, "role_id" => 6, "bio" => null, "created_at" => "2025-11-26 12:43:10", "updated_at" => "2025-11-26 12:43:10", "deleted_at" => null],
        ];
    }
}

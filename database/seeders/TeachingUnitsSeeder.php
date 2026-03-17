<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeachingUnitsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('teaching_units')->truncate();
        DB::table('teaching_units')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "a4d830cc-913d-4d24-88de-2b4ca5cc0db6", "name" => "Mathématiques pour ingénieur", "code" => "MATH101", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:08:50", "updated_at" => "2025-11-26 12:08:50", "deleted_at" => null],
            ["id" => 2, "uuid" => "e092a6ab-504e-4b56-ba80-b6ee2e6dec30", "name" => "Mathématiques pour sciences physiques", "code" => "MATH102", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:09:19", "updated_at" => "2025-11-26 12:09:19", "deleted_at" => null],
            ["id" => 3, "uuid" => "3b83e7f4-e4cb-4014-9376-a35144bc384c", "name" => "Analyse Numerique", "code" => "AN101", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:09:37", "updated_at" => "2025-11-26 12:09:37", "deleted_at" => null],
            ["id" => 4, "uuid" => "97873dc0-29c8-49a2-aac7-e3d792ccf5d8", "name" => "Initiation à l'algorithmique", "code" => "IA101", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:09:58", "updated_at" => "2025-11-26 12:09:58", "deleted_at" => null],
            ["id" => 5, "uuid" => "7e000ea5-3e01-4e51-9085-c43a5c274bcb", "name" => "Langage et Programmations", "code" => "LP", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:10:15", "updated_at" => "2025-11-26 12:10:15", "deleted_at" => null],
            ["id" => 6, "uuid" => "4dd8417f-c681-43e3-bc8e-88f076e15af3", "name" => "Mécanique des Milieux Continus", "code" => "MMC", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:10:38", "updated_at" => "2025-11-26 12:10:38", "deleted_at" => null],
            ["id" => 7, "uuid" => "5e42e398-95bd-4fcd-b104-2c460f9bf7a9", "name" => "Mécaniques des Fluides", "code" => "MF", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:10:54", "updated_at" => "2025-11-26 12:10:54", "deleted_at" => null],
            ["id" => 8, "uuid" => "052b0e1e-679a-4e60-87e1-ef96bd867aa5", "name" => "Recherche Opérationnelle", "code" => "RO", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:14:02", "updated_at" => "2025-11-26 12:14:02", "deleted_at" => null],
            ["id" => 9, "uuid" => "c1653bab-535c-462a-9849-11e2e5549a85", "name" => "Ondes et Electromagnétisme", "code" => "OEM", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:14:24", "updated_at" => "2025-11-26 12:14:24", "deleted_at" => null],
            ["id" => 10, "uuid" => "ad3801d1-150e-4a80-9360-6a54fb9a334e", "name" => "Electricité Générale", "code" => "EG", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:14:40", "updated_at" => "2025-11-26 12:14:40", "deleted_at" => null],
            ["id" => 11, "uuid" => "cf7f7d59-a009-45f7-9868-31efae0c2c31", "name" => "Technologie des Composants Electroniques", "code" => "TCE", "program_id" => null, "credits" => 0, "coefficient" => 1.00, "description" => null, "is_mandatory" => 1, "created_at" => "2025-11-26 12:15:03", "updated_at" => "2025-11-26 12:15:03", "deleted_at" => null],
        ];
    }
}

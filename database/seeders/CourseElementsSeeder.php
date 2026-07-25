<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseElementsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('course_elements')->truncate();
        DB::table('course_elements')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "041a7336-65f0-4e2b-b297-021611c55079", "name" => "Mathématiques pour ingénieur", "code" => "MPI", "credits" => 1, "teaching_unit_id" => 1, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:11:23", "updated_at" => "2025-11-26 12:11:23", "deleted_at" => null],
            ["id" => 2, "uuid" => "9aebc436-ad30-40f4-8654-e0d438788322", "name" => "Mathématiques pour sciences physiques", "code" => "MSP", "credits" => 1, "teaching_unit_id" => 2, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:11:47", "updated_at" => "2025-11-26 12:11:47", "deleted_at" => null],
            ["id" => 3, "uuid" => "89bd2ffa-95f6-447f-bf7d-863157edc910", "name" => "Analyse Numerique", "code" => "ANU", "credits" => 1, "teaching_unit_id" => 3, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:12:02", "updated_at" => "2025-11-26 12:12:02", "deleted_at" => null],
            ["id" => 4, "uuid" => "ff564e08-91b1-4601-be40-68c842b74329", "name" => "Initiation à l'algorithmique", "code" => "IAA", "credits" => 1, "teaching_unit_id" => 4, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:12:17", "updated_at" => "2025-11-26 12:12:17", "deleted_at" => null],
            ["id" => 5, "uuid" => "eed7fb58-d6d7-4dbd-a062-46856778edea", "name" => "Langage et Programmations", "code" => "LEP", "credits" => 1, "teaching_unit_id" => 5, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:12:35", "updated_at" => "2025-11-26 12:12:35", "deleted_at" => null],
            ["id" => 6, "uuid" => "240bfeab-e506-4b29-80f4-2546342b8e55", "name" => "Mécanique des Milieux Continus", "code" => "MMC", "credits" => 1, "teaching_unit_id" => 6, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:12:57", "updated_at" => "2025-11-26 12:12:57", "deleted_at" => null],
            ["id" => 7, "uuid" => "8c577210-4ac7-4d60-a03b-808d11bae831", "name" => "Mécaniques des Fluides", "code" => "MDF", "credits" => 1, "teaching_unit_id" => 7, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:13:11", "updated_at" => "2025-11-26 12:13:11", "deleted_at" => null],
            ["id" => 8, "uuid" => "937deac1-051a-44f4-b3c4-a94904dd7287", "name" => "Recherche Opérationnelle", "code" => "ROP", "credits" => 1, "teaching_unit_id" => 8, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:25:11", "updated_at" => "2025-11-26 12:25:11", "deleted_at" => null],
            ["id" => 9, "uuid" => "9467d9a7-fe56-4c60-a1f7-3936e4da0597", "name" => "Ondes et Electromagnétisme", "code" => "OEM", "credits" => 1, "teaching_unit_id" => 9, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:25:37", "updated_at" => "2025-11-26 12:25:37", "deleted_at" => null],
            ["id" => 10, "uuid" => "536714b2-4b01-4db8-ab93-01f82bd56c59", "name" => "Electricité Générale", "code" => "ELG", "credits" => 1, "teaching_unit_id" => 10, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:25:56", "updated_at" => "2025-11-26 12:25:56", "deleted_at" => null],
            ["id" => 11, "uuid" => "3e0ef3dc-7d6f-4e21-9f3e-c91272d92219", "name" => "Technologie des Composants Electroniques", "code" => "TCE", "credits" => 1, "teaching_unit_id" => 11, "type" => null, "hours" => 0, "coefficient" => 1.00, "description" => null, "objectives" => null, "created_at" => "2025-11-26 12:26:15", "updated_at" => "2025-11-26 12:26:15", "deleted_at" => null],
        ];
    }
}

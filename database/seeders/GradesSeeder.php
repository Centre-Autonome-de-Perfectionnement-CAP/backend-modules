<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('grades')->truncate();
        DB::table('grades')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 1, "uuid" => "6550635c-c91b-11f0-b164-f1f70328c04a", "name" => "Professeur Titulaire", "code" => "Pr", "level" => 5, "description" => "Professeur Titulaire des Universités du CAMES", "created_at" => "2025-11-24 09:53:14", "updated_at" => "2025-11-24 09:53:14"],
            ["id" => 2, "uuid" => "65507ef7-c91b-11f0-b164-f1f70328c04a", "name" => "Maitre de Conférences", "code" => "Dr(MC)", "level" => 4, "description" => "Maitre de Conférences des Universités du CAMES", "created_at" => "2025-11-24 09:53:14", "updated_at" => "2025-11-24 09:53:14"],
            ["id" => 3, "uuid" => "65508095-c91b-11f0-b164-f1f70328c04a", "name" => "Maitre-Assistant", "code" => "Dr(MA)", "level" => 3, "description" => "Maitre-Assistant des Universités du CAMES", "created_at" => "2025-11-24 09:53:14", "updated_at" => "2025-11-24 09:53:14"],
            ["id" => 4, "uuid" => "65508134-c91b-11f0-b164-f1f70328c04a", "name" => "Docteur", "code" => "Dr", "level" => 2, "description" => "Docteur", "created_at" => "2025-11-24 09:53:14", "updated_at" => "2025-11-24 09:53:14"],
            ["id" => 5, "uuid" => "6550821f-c91b-11f0-b164-f1f70328c04a", "name" => "Monsieur", "code" => "Mr", "level" => 1, "description" => "Enseignant sans grade académique spécifique (Monsieur)", "created_at" => "2025-11-24 09:53:14", "updated_at" => "2025-11-24 09:53:14"],
        ];
    }
}

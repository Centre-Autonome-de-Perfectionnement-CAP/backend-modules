<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FileActivitiesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('file_activities')->truncate();
        DB::table('file_activities')->insert($this->data());
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function data(): array
    {
        return [
            ["id" => 13, "uuid" => "46d6f8ab-5cfc-40ed-9b5a-fcd624c3ede5", "file_id" => 13, "user_id" => null, "action" => "uploaded", "ip_address" => "41.85.185.28", "user_agent" => "PostmanRuntime/7.49.1", "metadata" => "{\"original_name\":\"Contrat de partenariat.pdf\"}", "created_at" => "2025-11-17 09:46:01", "updated_at" => null],
            ["id" => 14, "uuid" => "82b37b30-0f44-46e6-b87c-b5667e27ba0c", "file_id" => 14, "user_id" => null, "action" => "uploaded", "ip_address" => "41.85.185.28", "user_agent" => "PostmanRuntime/7.49.1", "metadata" => "{\"original_name\":\"Contrat de partenariat.pdf\"}", "created_at" => "2025-11-17 09:46:01", "updated_at" => null],
            ["id" => 15, "uuid" => "5da0aa72-4191-49f7-9de1-d73bf3ade1cf", "file_id" => 15, "user_id" => null, "action" => "uploaded", "ip_address" => "41.85.185.28", "user_agent" => "PostmanRuntime/7.49.1", "metadata" => "{\"original_name\":\"Contrat de partenariat.pdf\"}", "created_at" => "2025-11-17 09:46:01", "updated_at" => null],
            ["id" => 16, "uuid" => "c9898b13-59a1-463c-ab22-ed559dca6b47", "file_id" => 16, "user_id" => null, "action" => "uploaded", "ip_address" => "41.85.185.28", "user_agent" => "PostmanRuntime/7.49.1", "metadata" => "{\"original_name\":\"Contrat de partenariat.pdf\"}", "created_at" => "2025-11-17 09:46:01", "updated_at" => null],
            ["id" => 17, "uuid" => "92602eac-b113-4988-8720-3d4214da3514", "file_id" => 17, "user_id" => null, "action" => "uploaded", "ip_address" => "41.85.185.28", "user_agent" => "PostmanRuntime/7.49.1", "metadata" => "{\"original_name\":\"Contrat de partenariat.pdf\"}", "created_at" => "2025-11-17 09:46:01", "updated_at" => null],
            ["id" => 18, "uuid" => "96c0b3a8-6c0d-434b-8c13-5ecbf78be633", "file_id" => 18, "user_id" => null, "action" => "uploaded", "ip_address" => "41.85.185.28", "user_agent" => "PostmanRuntime/7.49.1", "metadata" => "{\"original_name\":\"Contrat de partenariat.pdf\"}", "created_at" => "2025-11-17 09:46:01", "updated_at" => null],
            ["id" => 19, "uuid" => "395551b2-d95c-47f0-92ec-a464a75503fd", "file_id" => 19, "user_id" => null, "action" => "uploaded", "ip_address" => "41.85.185.28", "user_agent" => "PostmanRuntime/7.49.1", "metadata" => "{\"original_name\":\"Contrat de partenariat.pdf\"}", "created_at" => "2025-11-17 09:46:01", "updated_at" => null],
        ];
    }
}

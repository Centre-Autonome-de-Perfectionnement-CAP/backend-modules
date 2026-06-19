<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renommage des données "chef-division" en "responsable-division"
 * suite à la demande d'harmonisation complète.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Table roles
        DB::table('roles')->where('slug', 'chef-division')->update(['slug' => 'responsable-division']);
        
        // 2. Historique
        DB::table('document_request_histories')->where('actor_role', 'chef-division')->update(['actor_role' => 'responsable-division']);
        
        // 3. Document requests (correction circuit origin)
        DB::table('document_requests')->where('correction_origin_role', 'chef-division')->update(['correction_origin_role' => 'responsable-division']);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'responsable-division')->update(['slug' => 'chef-division']);
        DB::table('document_request_histories')->where('actor_role', 'responsable-division')->update(['actor_role' => 'chef-division']);
        DB::table('document_requests')->where('correction_origin_role', 'responsable-division')->update(['correction_origin_role' => 'chef-division']);
    }
};

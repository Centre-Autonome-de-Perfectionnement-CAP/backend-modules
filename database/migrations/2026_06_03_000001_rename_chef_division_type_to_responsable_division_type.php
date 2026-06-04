<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Renommage de la colonne chef_division_type → responsable_division_type
 * dans document_requests.
 *
 * Raison : harmonisation terminologique — on parle désormais de
 * "Responsable Division" et non plus de "Chef Division".
 *
 * Les valeurs stockées (formation_distance / formation_continue) ne changent pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Supprimer l'index existant avant le renommage
        try {
            DB::statement('ALTER TABLE document_requests DROP INDEX dr_chef_division_type_idx');
        } catch (\Throwable) {
            // Index absent : rien à faire
        }

        Schema::table('document_requests', function (Blueprint $table) {
            $table->renameColumn('chef_division_type', 'responsable_division_type');
        });

        // Recréer l'index avec le nouveau nom de colonne
        DB::statement('CREATE INDEX dr_responsable_division_type_idx ON document_requests (responsable_division_type)');
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE document_requests DROP INDEX dr_responsable_division_type_idx');
        } catch (\Throwable) {}

        Schema::table('document_requests', function (Blueprint $table) {
            $table->renameColumn('responsable_division_type', 'chef_division_type');
        });

        DB::statement('CREATE INDEX dr_chef_division_type_idx ON document_requests (chef_division_type)');
    }
};

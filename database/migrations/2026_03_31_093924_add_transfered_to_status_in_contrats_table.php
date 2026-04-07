<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute 'transfered' à l'enum status de la table contrats.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE contrats
            MODIFY COLUMN status
            ENUM('pending','signed','ongoing','completed','cancelled','transfered')
            NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     * Retire 'transfered' de l'enum (les lignes ayant ce statut doivent être migrées avant).
     */
    public function down(): void
    {
        // Repasse les lignes 'transfered' en 'completed' pour éviter une erreur de contrainte
        DB::statement("UPDATE contrats SET status = 'completed' WHERE status = 'transfered'");

        DB::statement("
            ALTER TABLE contrats
            MODIFY COLUMN status
            ENUM('pending','signed','ongoing','completed','cancelled')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
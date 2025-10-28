<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier directement via SQL raw pour éviter les problèmes de Schema
        DB::statement('ALTER TABLE personal_information MODIFY birth_date DATE NULL');
        
        // Aussi pour les autres colonnes potentiellement problématiques
        DB::statement('ALTER TABLE personal_information MODIFY birth_place VARCHAR(255) NULL');
        DB::statement('ALTER TABLE personal_information MODIFY birth_country VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne rien faire
    }
};

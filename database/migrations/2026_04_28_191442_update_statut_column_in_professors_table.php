<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Déterminer si le nom est 'status' ou 'statut'
        $column = null;
        if (Schema::hasColumn('professors', 'status')) {
            $column = 'status';
        } elseif (Schema::hasColumn('professors', 'statut')) {
            $column = 'statut';
        }

        // 2. Si la colonne existe, on applique la modification via SQL brut (plus fiable pour ENUM)
        if ($column) {
            DB::statement("ALTER TABLE professors MODIFY COLUMN $column ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $column = null;
        if (Schema::hasColumn('professors', 'status')) {
            $column = 'status';
        } elseif (Schema::hasColumn('professors', 'statut')) {
            $column = 'statut';
        }

        if ($column) {
            // On retire 'suspended'
            // Attention : s'il y a des professeurs 'suspended' en DB, MySQL risque de râler
            DB::statement("ALTER TABLE professors MODIFY COLUMN $column ENUM('active', 'inactive') DEFAULT 'active'");
        }
    }
};
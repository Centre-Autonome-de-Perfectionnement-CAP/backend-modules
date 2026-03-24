<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professors', function (Blueprint $table) {
            // ✅ 1. Supprimer la clé étrangère d'abord
            $table->dropForeign('professors_rib_foreign');
            $table->dropForeign('professors_ifu_foreign'); // au cas où ifu aussi

            // ✅ 2. Modifier les colonnes
            $table->string('rib')->nullable()->change();
            $table->string('ifu')->nullable()->change();

            // ✅ 3. Ajouter rib_url et ifu_url si absents
            if (!Schema::hasColumn('professors', 'rib_url')) {
                $table->string('rib_url')->nullable()->after('rib');
            }
            if (!Schema::hasColumn('professors', 'ifu_url')) {
                $table->string('ifu_url')->nullable()->after('ifu');
            }
        });

        // ✅ 4. Corriger l'enum statut
        DB::statement("ALTER TABLE professors MODIFY COLUMN statut ENUM('active', 'inactive', 'suspended') DEFAULT 'active'");
    }

    public function down(): void
    {
        Schema::table('professors', function (Blueprint $table) {
            $table->unsignedBigInteger('rib')->nullable()->change();
            $table->unsignedBigInteger('ifu')->nullable()->change();
        });

        DB::statement("ALTER TABLE professors MODIFY COLUMN statut ENUM('active', 'inactive') DEFAULT 'active'");
    }
};
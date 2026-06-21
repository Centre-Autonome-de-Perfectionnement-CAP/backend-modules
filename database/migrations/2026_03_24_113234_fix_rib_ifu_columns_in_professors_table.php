<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Désactiver les contraintes FK
        Schema::disableForeignKeyConstraints();

        // Supprimer les foreign keys si elles existent
        try {
            Schema::table('professors', function (Blueprint $table) {
                $table->dropForeign('professors_rib_foreign');
                $table->dropForeign('professors_ifu_foreign');
            });
        } catch (\Exception $e) {
            // ignore si déjà supprimées
        }

        // Renommer colonne statut -> status
        if (Schema::hasColumn('professors', 'statut')) {
            Schema::table('professors', function (Blueprint $table) {
                $table->renameColumn('statut', 'status');
            });
        }

        // Modifier rib et ifu en string
        Schema::table('professors', function (Blueprint $table) {
            $table->string('rib')->nullable()->change();
            $table->string('ifu')->nullable()->change();

            if (!Schema::hasColumn('professors', 'rib_url')) {
                $table->string('rib_url')->nullable()->after('rib');
            }

            if (!Schema::hasColumn('professors', 'ifu_url')) {
                $table->string('ifu_url')->nullable()->after('ifu');
            }
        });

        // 🔥 Correction propre du ENUM (IMPORTANT)
        DB::statement("
            ALTER TABLE professors
            MODIFY status ENUM('active','inactive','suspended')
            NOT NULL
            DEFAULT 'active'
        ");

        // Réactiver les contraintes FK
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Retour en arrière simple (adaptable selon ton ancien schéma)
        Schema::table('professors', function (Blueprint $table) {
            $table->unsignedBigInteger('rib')->nullable()->change();
            $table->unsignedBigInteger('ifu')->nullable()->change();
        });

        DB::statement("
            ALTER TABLE professors
            MODIFY status ENUM('active','inactive')
            NOT NULL
            DEFAULT 'active'
        ");
    }
};

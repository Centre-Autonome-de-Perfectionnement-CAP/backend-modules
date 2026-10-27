<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Désactiver les contraintes au niveau global
        Schema::disableForeignKeyConstraints();

        // 2. Supprimer les clés étrangères physiquement
        // On utilise try/catch pour éviter que ça plante si elles ont déjà été supprimées
        try {
            Schema::table('professors', function (Blueprint $table) {
                $table->dropForeign('professors_rib_foreign');
                $table->dropForeign('professors_ifu_foreign');
            });
        } catch (\Exception $e) {
            // Si l'erreur est que la clé n'existe pas, on continue
        }

        // 3. Modifier les colonnes
        Schema::table('professors', function (Blueprint $table) {
            // Renommage statut -> status
            if (Schema::hasColumn('professors', 'statut')) {
              DB::statement("
    ALTER TABLE professors
    CHANGE statut status
    ENUM('active','inactive','suspended')
    NOT NULL DEFAULT 'active'
");
            }

            // Changement des types en string (Maintenant possible car la FK est supprimée)
            $table->string('rib')->nullable()->change();
            $table->string('ifu')->nullable()->change();

            if (!Schema::hasColumn('professors', 'rib_url')) {
                $table->string('rib_url')->nullable()->after('rib');
            }
            if (!Schema::hasColumn('professors', 'ifu_url')) {
                $table->string('ifu_url')->nullable()->after('ifu');
            }
        });

        // 4. Modifier l'ENUM
        DB::statement("ALTER TABLE professors MODIFY COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' ");

        // 5. Réactiver les contraintes
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::table('professors', function (Blueprint $table) {
            $table->unsignedBigInteger('rib')->nullable()->change();
            $table->unsignedBigInteger('ifu')->nullable()->change();
        });
    }
};

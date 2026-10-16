<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop indexes on level if SQLite or MySQL
        try {
            Schema::table('amounts', function (Blueprint $table) {
                $table->dropUnique('amounts_unique_combination');
                $table->dropIndex(['level']);
            });
        } catch (\Throwable $e) {
            // Index might not exist or already dropped
        }

        // FUSION develop/main :
        //  - develop : supprimer la clé étrangère program_id AVANT la colonne
        //    (obligatoire sous MySQL si la contrainte existe) ;
        //  - main    : suppression groupée et idempotente des colonnes.
        // La clé étrangère est supprimée dans un appel séparé : l'instruction SQL
        // n'est exécutée qu'à la fin de Schema::table(), c'est donc l'appel
        // entier qu'il faut protéger (contrainte éventuellement absente).
        if (Schema::hasColumn('amounts', 'program_id')) {
            try {
                Schema::table('amounts', function (Blueprint $table) {
                    $table->dropForeign(['program_id']);
                });
            } catch (\Throwable $e) {
                // Pas de clé étrangère sur program_id
            }
        }

        Schema::table('amounts', function (Blueprint $table) {
            $colsToDrop = [];
            foreach (['program_id', 'level', 'sponsored_amount'] as $col) {
                if (Schema::hasColumn('amounts', $col)) {
                    $colsToDrop[] = $col;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable();
            $table->integer('level')->nullable();
            $table->decimal('sponsored_amount', 10, 2)->default(0);
        });
    }
};
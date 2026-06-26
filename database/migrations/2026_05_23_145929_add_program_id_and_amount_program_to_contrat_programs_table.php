<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter program_id s'il n'existe pas
        if (!Schema::hasColumn('contrat_programs', 'program_id')) {
            Schema::table('contrat_programs', function (Blueprint $table) {
                $table->unsignedBigInteger('program_id')->nullable();
            });
        } else {
            // Le rendre nullable s'il existe déjà
            Schema::table('contrat_programs', function (Blueprint $table) {
                $table->unsignedBigInteger('program_id')->nullable()->change();
            });
        }

        // Nettoyer les références invalides
        DB::statement("
            UPDATE contrat_programs
            SET program_id = NULL
            WHERE program_id IS NOT NULL
            AND program_id NOT IN (SELECT id FROM programs)
        ");

        // Ajouter la clé étrangère si elle n'existe pas
        $fkExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'contrat_programs')
            ->where('CONSTRAINT_NAME', 'contrat_programs_program_id_foreign')
            ->exists();

        if (!$fkExists) {
            Schema::table('contrat_programs', function (Blueprint $table) {
                $table->foreign('program_id')
                    ->references('id')
                    ->on('programs')
                    ->cascadeOnDelete();
            });
        }

        // Ajouter amount_program s'il n'existe pas
        if (!Schema::hasColumn('contrat_programs', 'amount_program')) {
            Schema::table('contrat_programs', function (Blueprint $table) {
                $table->decimal('amount_program', 15, 2)
                    ->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('contrat_programs', function (Blueprint $table) {

            if (Schema::hasColumn('contrat_programs', 'amount_program')) {
                $table->dropColumn('amount_program');
            }

            try {
                $table->dropForeign(['program_id']);
            } catch (\Throwable $e) {
                // Ignore si la FK n'existe pas
            }

            if (Schema::hasColumn('contrat_programs', 'program_id')) {
                $table->dropColumn('program_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void {
        // 1. Rendre la colonne nullable (IMPORTANT)
        Schema::table('contrat_programs', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->change();
        });

        // 2. Nettoyer les données invalides
        DB::statement("
            UPDATE contrat_programs
            SET program_id = NULL
            WHERE program_id NOT IN (SELECT id FROM programs)
        ");

        // 3. Ajouter FK si pas encore existante
        $fkExists = collect(DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'contrat_programs'
            AND CONSTRAINT_NAME = 'contrat_programs_program_id_foreign'
        "))->isNotEmpty();

        if (!$fkExists) {
            Schema::table('contrat_programs', function (Blueprint $table) {
                $table->foreign('program_id')
                    ->references('id')
                    ->on('programs')
                    ->onDelete('cascade');
            });
        }

        // 4. Ajouter amount_program
        if (!Schema::hasColumn('contrat_programs', 'amount_program')) {
            Schema::table('contrat_programs', function (Blueprint $table) {
                $table->decimal('amount_program', 15, 2)
                    ->nullable()
                    ->after('program_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('contrat_programs', function (Blueprint $table) {

            if (Schema::hasColumn('contrat_programs', 'program_id')) {
                try {
                    $table->dropForeign(['program_id']);
                } catch (\Throwable $e) {
                    // ignore si FK inexistante
                }
            }

            if (Schema::hasColumn('contrat_programs', 'amount_program')) {
                $table->dropColumn('amount_program');
            }
        });
    }
};
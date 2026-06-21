<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier que la table existe
        if (!Schema::hasTable('contrat_programs')) {
            return;
        }

        // 1. Rendre program_id nullable si la colonne existe
        if (Schema::hasColumn('contrat_programs', 'program_id')) {
            try {
                Schema::table('contrat_programs', function (Blueprint $table) {
                    $table->unsignedBigInteger('program_id')
                        ->nullable()
                        ->change();
                });
            } catch (\Throwable $e) {
                // Ignorer l'erreur si le changement est impossible
            }
        }

        // 2. Nettoyer les données invalides
        if (
            Schema::hasColumn('contrat_programs', 'program_id') &&
            Schema::hasTable('programs')
        ) {
            try {
                DB::statement("
                    UPDATE contrat_programs
                    SET program_id = NULL
                    WHERE program_id IS NOT NULL
                    AND program_id NOT IN (SELECT id FROM programs)
                ");
            } catch (\Throwable $e) {
                // Ignorer
            }
        }

        // 3. Ajouter la FK uniquement si nécessaire
        if (
            Schema::hasColumn('contrat_programs', 'program_id') &&
            Schema::hasTable('programs')
        ) {
            try {
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
            } catch (\Throwable $e) {
                // Ignorer
            }
        }

        // 4. Ajouter amount_program si absent
        if (!Schema::hasColumn('contrat_programs', 'amount_program')) {
            try {
                Schema::table('contrat_programs', function (Blueprint $table) {
                    $table->decimal('amount_program', 15, 2)
                        ->nullable()
                        ->after('program_id');
                });
            } catch (\Throwable $e) {
                // Ignorer
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('contrat_programs')) {
            return;
        }

        // Supprimer la FK si elle existe
        if (Schema::hasColumn('contrat_programs', 'program_id')) {
            try {
                Schema::table('contrat_programs', function (Blueprint $table) {
                    $table->dropForeign('contrat_programs_program_id_foreign');
                });
            } catch (\Throwable $e) {
                // Ignorer
            }
        }

        // Supprimer amount_program si elle existe
        if (Schema::hasColumn('contrat_programs', 'amount_program')) {
            try {
                Schema::table('contrat_programs', function (Blueprint $table) {
                    $table->dropColumn('amount_program');
                });
            } catch (\Throwable $e) {
                // Ignorer
            }
        }
    }
};

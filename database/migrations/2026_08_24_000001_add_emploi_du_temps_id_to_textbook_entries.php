<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX (unification emploi du temps) — le cahier de texte référençait
 * uniquement `scheduled_courses` (ancien système), alors que le module
 * "Gestion emploi du temps" (utilisé en pratique) écrit dans
 * `emploi_du_temps` (nouveau système). Les deux tables ne communiquaient
 * pas : impossible de rattacher une séance créée dans le nouveau système
 * à une entrée de cahier de texte.
 *
 * `scheduled_course_id` est conservé (ne pas casser les entrées déjà
 * créées avec l'ancien système) ; `emploi_du_temps_id` devient la
 * référence utilisée pour toute nouvelle entrée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('textbook_entries', function (Blueprint $table) {
            $table->foreignId('emploi_du_temps_id')
                ->nullable()
                ->after('scheduled_course_id')
                ->constrained('emploi_du_temps')
                ->onDelete('set null');
            $table->index('emploi_du_temps_id');
        });
    }

    public function down(): void
    {
        Schema::table('textbook_entries', function (Blueprint $table) {
            $table->dropForeign(['emploi_du_temps_id']);
            $table->dropIndex(['emploi_du_temps_id']);
            $table->dropColumn('emploi_du_temps_id');
        });
    }
};

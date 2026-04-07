<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Table : emploi_du_temps
     */
    public function up(): void
    {
        // Désactiver les contraintes pour éviter les erreurs lors du drop
        Schema::disableForeignKeyConstraints();

        // Vérifie si la table existe puis la supprime
        if (Schema::hasTable('emploi_du_temps')) {
            Schema::drop('emploi_du_temps');
        }

        // Réactiver les contraintes
        Schema::enableForeignKeyConstraints();

        // Création de la nouvelle table
        Schema::create('emploi_du_temps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ── Contexte académique ──────────────────────────────
            $table->foreignId('academic_year_id')
                  ->constrained('academic_years')
                  ->cascadeOnDelete();

            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->cascadeOnDelete();

            $table->foreignId('class_group_id')
                  ->constrained('class_groups')
                  ->cascadeOnDelete();

            $table->foreignId('program_id')
                  ->constrained('programs')
                  ->cascadeOnDelete();

            // ── Lieu ─────────────────────────────────────────────
            $table->foreignId('room_id')
                  ->constrained('rooms')
                  ->cascadeOnDelete();

            // ── Créneau hebdomadaire ─────────────────────────────
            $table->enum('day_of_week', [
                'monday', 'tuesday', 'wednesday',
                'thursday', 'friday', 'saturday', 'sunday',
            ]);

            $table->time('start_time');
            $table->time('end_time');

            // ── Récurrence / validité ────────────────────────────
            $table->boolean('is_recurring')->default(true);
            $table->date('recurrence_end_date')->nullable();
            $table->json('excluded_dates')->nullable();

            // ── Métadonnées ──────────────────────────────────────
            $table->text('notes')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // ── Index ────────────────────────────────────────────
            $table->index(
                ['academic_year_id', 'department_id', 'class_group_id'],
                'edt_context_idx'
            );

            $table->index(
                ['academic_year_id', 'program_id'],
                'edt_program_idx'
            );

            $table->index(
                ['room_id', 'day_of_week', 'start_time'],
                'edt_room_slot_idx'
            );

            $table->index(
                ['day_of_week', 'start_time', 'end_time'],
                'edt_time_idx'
            );

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('emploi_du_temps');
        Schema::enableForeignKeyConstraints();
    }
};
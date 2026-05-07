<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : emploi_du_temps
     *
     * Structure simplifiée :
     *  ✓ Ajout de class_group_id (sélection directe de la classe)
     *  ✗ Suppression date_start / date_end (pas de sens dans un EDT hebdomadaire)
     *  ✗ Suppression total_hours / hours_completed (calculé auto via start_time - end_time)
     *  ✓ duration_in_minutes est un champ calculé (accesseur Eloquent), pas stocké
     */
    public function up(): void
    {
        Schema::create('emploi_du_temps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // ── Contexte académique ──────────────────────────────
            $table->foreignId('academic_year_id')
                  ->constrained('academic_years')
                  ->onDelete('cascade');

            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->onDelete('cascade');

            // Classe sélectionnée directement (évite l'ambiguïté via program)
            $table->foreignId('class_group_id')
                  ->constrained('class_groups')
                  ->onDelete('cascade');

            // Programme = cours + professeur (filtré par class_group_id + academic_year_id)
            $table->foreignId('program_id')
                  ->constrained('programs')
                  ->onDelete('cascade');

            // ── Lieu ─────────────────────────────────────────────
            $table->foreignId('room_id')
                  ->constrained('rooms')
                  ->onDelete('cascade');

            // ── Créneau hebdomadaire ─────────────────────────────
            $table->enum('day_of_week', [
                'monday', 'tuesday', 'wednesday',
                'thursday', 'friday', 'saturday', 'sunday',
            ]);

            // HH:MM  (ex: 08:00 — 10:00)
            $table->time('start_time');
            $table->time('end_time');

            // ── Récurrence / validité ────────────────────────────
            $table->boolean('is_recurring')->default(true);
            // Date de fin de validité du créneau (ex: fin de semestre)
            $table->date('recurrence_end_date')->nullable();
            // Dates exclues (jours fériés, rattrapages…) au format JSON ["2024-12-25","2025-01-01"]
            $table->json('excluded_dates')->nullable();

            // ── Métadonnées ──────────────────────────────────────
            $table->text('notes')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // ── Index pour les recherches fréquentes ─────────────
            $table->index(['academic_year_id', 'department_id', 'class_group_id'], 'edt_context_idx');
            $table->index(['academic_year_id', 'program_id'],                      'edt_program_idx');
            $table->index(['room_id', 'day_of_week', 'start_time'],                'edt_room_slot_idx');
            $table->index(['day_of_week', 'start_time', 'end_time'],               'edt_time_idx');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emploi_du_temps');
    }
};
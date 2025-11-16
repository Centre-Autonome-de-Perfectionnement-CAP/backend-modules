<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_courses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->foreignId('program_id')
                ->constrained('programs')
                ->onDelete('cascade')
                ->comment('Programme de cours (CourseElement + Professor + ClassGroup)');
            
            $table->foreignId('time_slot_id')
                ->constrained('time_slots')
                ->onDelete('cascade')
                ->comment('Créneau horaire');
            
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->onDelete('cascade')
                ->comment('Salle de cours');
            
            $table->date('start_date')->comment('Date de début du cours');
            $table->date('end_date')->nullable()->comment('Date de fin calculée ou manuelle');
            
            $table->decimal('total_hours', 8, 2)->default(0)->comment('Masse horaire totale prévue');
            $table->decimal('hours_completed', 8, 2)->default(0)->comment('Heures effectuées');
            
            $table->boolean('is_recurring')->default(true)->comment('Cours récurrent hebdomadaire');
            $table->date('recurrence_end_date')->nullable()->comment('Date de fin de récurrence');
            $table->json('excluded_dates')->nullable()->comment('Dates exclues (jours fériés, vacances)');
            
            $table->text('notes')->nullable();
            $table->boolean('is_cancelled')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index('program_id');
            $table->index('time_slot_id');
            $table->index('room_id');
            $table->index('start_date');
            $table->index('is_recurring');
            $table->index('is_cancelled');
            
            // Index composé pour recherches fréquentes
            $table->index(['start_date', 'time_slot_id', 'room_id'], 'schedule_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_courses');
    }
};

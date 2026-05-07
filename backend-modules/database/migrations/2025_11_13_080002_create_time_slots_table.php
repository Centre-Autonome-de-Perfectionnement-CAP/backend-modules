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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->enum('day_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ]);
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', [
                'lecture',  // Cours magistral
                'td',       // Travaux dirigés
                'tp',       // Travaux pratiques
                'exam'      // Examen
            ])->default('lecture');
            $table->string('name')->nullable()->comment('Nom optionnel du créneau, ex: "Matinée", "Après-midi"');
            
            $table->timestamps();
            
            // Indexes
            $table->index('day_of_week');
            $table->index(['day_of_week', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};

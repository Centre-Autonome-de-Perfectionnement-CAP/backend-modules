<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Table "seances" = équivalent de struct Course de l'Arduino
    // Représente une séance de cours planifiée à une date précise
    // Différent de emploi_du_temps (planning récurrent)
    // Une séance est une occurrence réelle : "Maths le 17/04/2026 à 08h00 pendant 90min"

    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table) {
            $table->id();

            // Lien vers l'élément de cours (matière)
            $table->foreignId('course_element_id')
                  ->constrained('course_elements')
                  ->onDelete('cascade');

            // Lien vers la filière
            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->onDelete('cascade');

            // Année académique (ex: "2025-2026")
            $table->string('academic_year', 20);

            // Niveau (ex: "L1", "L2", "L3")
            $table->string('niveau', 10)->default('L1');

            // Date et horaire de la séance
            $table->date('date');
            $table->time('start_time');
            $table->integer('duration')->default(90); // durée en minutes

            // Salle (optionnel)
            $table->foreignId('room_id')
                  ->nullable()
                  ->constrained('rooms')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
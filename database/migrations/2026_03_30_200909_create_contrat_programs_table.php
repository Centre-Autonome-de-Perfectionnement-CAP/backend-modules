<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de liaison entre un contrat et les programmes assignés
     * (course_element_professor → programme : Professeur + Matière + Classe)
     */
    public function up(): void
    {
        Schema::create('contrat_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrat_id')
                  ->constrained('contrats')
                  ->onDelete('cascade');
            $table->foreignId('course_element_professor_id')
                  ->constrained('course_element_professor')
                  ->onDelete('cascade');
            $table->timestamps();

            // Un programme ne peut être lié qu'une seule fois au même contrat
            $table->unique(['contrat_id', 'course_element_professor_id'], 'contrat_program_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_programs');
    }
};
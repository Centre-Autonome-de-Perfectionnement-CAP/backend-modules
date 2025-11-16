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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            $table->foreignId('class_group_id')
                ->constrained('class_groups')
                ->onDelete('cascade');
                
            $table->foreignId('course_element_professor_id')
                ->constrained('course_element_professor')
                ->onDelete('cascade')
                ->comment('Référence à la table pivot course_element_professor');
                
            $table->json('weighting')
                ->nullable()
                ->comment('Pondération des évaluations (CC, TP, Examen, etc.)');
            
            $table->timestamps();
            
            // Indexes
            $table->index('class_group_id');
            $table->index('course_element_professor_id');
            $table->unique(['class_group_id', 'course_element_professor_id'], 'program_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
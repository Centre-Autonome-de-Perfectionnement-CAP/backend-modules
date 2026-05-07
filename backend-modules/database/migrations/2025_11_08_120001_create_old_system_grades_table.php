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
        Schema::create('old_system_grades', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_pending_student_id')
                ->constrained('student_pending_student')
                ->onDelete('cascade');
            $table->foreignId('program_id')
                ->constrained('programs')
                ->onDelete('cascade');
            $table->json('grades')->nullable()->comment('Store multiple grades (assignments, quizzes, etc.)');
            $table->decimal('average', 5, 2)->nullable()->comment('Calculated average grade');
            $table->timestamps();

            // Indexes
            $table->index('student_pending_student_id');
            $table->index('program_id');
            
            // One student can have only one set of grades per program
            $table->unique(['student_pending_student_id', 'program_id'], 'old_system_student_program_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_system_grades');
    }
};

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
        Schema::create('lmd_system_grades', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_pending_student_id')
                ->constrained('student_pending_student')
                ->onDelete('cascade');
            $table->foreignId('program_id')
                ->constrained('programs')
                ->onDelete('cascade');
            
            // Normal grades and average
            $table->json('grades')->nullable()->comment('Regular grades (CC, TP, Exam)');
            $table->decimal('average', 5, 2)->nullable()->comment('Calculated average');
            
            // Retake grades and average
            $table->json('retake_grades')->nullable()->comment('Retake/remedial grades');
            $table->decimal('retake_average', 5, 2)->nullable()->comment('Average after retake');
            
            // Status flags
            $table->boolean('validated')->default(false)->comment('Grade validated (>= 10/20)');
            $table->boolean('retaken')->default(false)->comment('Has taken retake exam');
            $table->boolean('must_retake')->default(false)->comment('Must retake the course');
            
            $table->timestamps();

            // Indexes
            $table->index('student_pending_student_id');
            $table->index('program_id');
            $table->index('validated');
            
            // One student can have only one set of grades per program
            $table->unique(['student_pending_student_id', 'program_id'], 'lmd_system_student_program_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lmd_system_grades');
    }
};

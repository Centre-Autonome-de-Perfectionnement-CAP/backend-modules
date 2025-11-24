<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_course_retakes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('student_pending_student_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('original_academic_year_id');
            $table->unsignedBigInteger('retake_academic_year_id');
            $table->string('original_study_level');
            $table->string('current_study_level');
            $table->enum('status', ['pending', 'in_progress', 'passed', 'failed'])->default('pending');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_pending_student_id')->references('id')->on('student_pending_students');
            $table->foreign('program_id')->references('id')->on('programs');
            $table->foreign('original_academic_year_id')->references('id')->on('academic_years');
            $table->foreign('retake_academic_year_id')->references('id')->on('academic_years');
            
            $table->unique(['student_pending_student_id', 'program_id', 'retake_academic_year_id'], 'unique_student_program_retake');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_course_retakes');
    }
};
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
            $table->unsignedBigInteger('student_pending_student_id')->index();
            $table->unsignedBigInteger('program_id')->index();
            $table->unsignedBigInteger('original_academic_year_id')->index();
            $table->unsignedBigInteger('retake_academic_year_id')->index();
            $table->string('original_study_level');
            $table->string('current_study_level');
            $table->enum('status', ['pending', 'in_progress', 'passed', 'failed'])->default('pending');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['student_pending_student_id', 'program_id', 'retake_academic_year_id'], 'unique_student_program_retake');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_course_retakes');
    }
};
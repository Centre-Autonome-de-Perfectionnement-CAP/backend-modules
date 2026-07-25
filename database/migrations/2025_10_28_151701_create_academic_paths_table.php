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
        Schema::create('academic_paths', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_pending_student_id')->nullable()->constrained('student_pending_student')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('study_level')->nullable();
            $table->enum('year_decision', ['pass', 'fail', 'repeat'])->nullable();
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->enum('financial_status', ['Exonéré', 'Non exonéré'])->default('Non exonéré');
            $table->string('cohort')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_pending_student_id');
            $table->index('academic_year_id');
            $table->index('financial_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_paths');
    }
};

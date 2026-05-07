<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amount_class_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amount_id')->constrained('amounts')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->integer('study_level');
            $table->timestamps();

            $table->unique(['amount_id', 'academic_year_id', 'department_id', 'study_level'], 'amount_class_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amount_class_groups');
    }
};

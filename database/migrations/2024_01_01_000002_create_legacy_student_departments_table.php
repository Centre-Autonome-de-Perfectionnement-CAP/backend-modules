<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_student_departments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('legacy_student_id')
                ->constrained('legacy_students')
                ->cascadeOnDelete();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();

            // Nullable: la filière rattachée n'a pas toujours un cycle connu
            // pour les anciennes promotions.
            $table->foreignId('cycle_id')
                ->nullable()
                ->constrained('cycles')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['legacy_student_id', 'department_id'],
                'legacy_student_department_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_student_departments');
    }
};

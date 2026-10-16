<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_student_academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_student_id')->constrained('legacy_students')->cascadeOnDelete();
            $table->string('academic_year'); // ex: "2018-2019" ou "2018"
            $table->string('level')->nullable(); // ex: "Licence 3", "Master 2"
            $table->string('semester')->nullable(); // ex: "Semestre 1 & 2"
            $table->decimal('general_average', 5, 2)->nullable(); // ex: 14.50
            $table->integer('total_credits')->default(60);
            $table->integer('obtained_credits')->default(60);
            $table->string('decision')->default('pass'); // pass (Admis), repeat (Redouble), fail (Ajourné)
            $table->string('mention')->nullable(); // Passable, Assez Bien, Bien, Très Bien
            $table->text('thesis_title')->nullable();
            $table->decimal('thesis_grade', 5, 2)->nullable();
            $table->date('thesis_date')->nullable();
            $table->boolean('quitus_accorded')->default(false);
            $table->json('courses')->nullable(); // Liste des matières / UEs avec notes
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_student_academic_records');
    }
};

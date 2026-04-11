<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            // Supprimer la FK sur course_element_id
            $table->dropForeign('course_element_professor_course_element_id_foreign');

            // Supprimer l'ancienne contrainte unique
            $table->dropUnique('course_professor_unique');

            // Créer la nouvelle contrainte unique incluant academic_year_id et class_group_id
            $table->unique(
                ['course_element_id', 'professor_id', 'academic_year_id', 'class_group_id'],
                'course_professor_unique'
            );

            // Recréer la FK sur course_element_id
            $table->foreign('course_element_id')
                  ->references('id')
                  ->on('course_elements')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            // Supprimer la nouvelle contrainte unique
            $table->dropUnique('course_professor_unique');

            // Supprimer la FK recréée
            $table->dropForeign('course_element_professor_course_element_id_foreign');

            // Recréer l’ancienne contrainte unique
            $table->unique(
                ['course_element_id', 'professor_id'],
                'course_professor_unique'
            );

            // Recréer la FK d’origine
            $table->foreign('course_element_id')
                  ->references('id')
                  ->on('course_elements')
                  ->onDelete('cascade');
        });
    }
};

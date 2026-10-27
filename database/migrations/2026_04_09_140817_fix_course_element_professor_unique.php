<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier si la contrainte unique 4-colonnes existe déjà (migration déjà jouée)
        $newUniqueExists = collect(
            DB::select("SHOW INDEX FROM course_element_professor WHERE Key_name = 'course_professor_unique'")
        )->count() >= 4; // 4 colonnes dans la contrainte

        if ($newUniqueExists) {
            return;
        }

        // Vérification AVANT toute modification (MySQL n'annule pas un
        // changement de structure à moitié fait) : des affectations en double
        // empêcheraient la nouvelle règle d'unicité.
        $duplicates = DB::table('course_element_professor')
            ->select('course_element_id', 'professor_id', 'academic_year_id', 'class_group_id')
            ->whereNotNull('academic_year_id')->whereNotNull('class_group_id')
            ->groupBy('course_element_id', 'professor_id', 'academic_year_id', 'class_group_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                $duplicates->count() . ' affectation(s) enseignant en double (même cours, enseignant, année et groupe) : '
                . 'rien n\'a été modifié. Supprimez les doublons puis relancez.'
            );
        }

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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            if (!Schema::hasColumn('emploi_du_temps', 'course_element_id')) {
                // Ajouter après program_id (qui existe déjà)
                $table->unsignedBigInteger('course_element_id')
                      ->nullable()
                      ->after('program_id');

                $table->foreign('course_element_id')
                      ->references('id')
                      ->on('course_elements')
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropForeign(['course_element_id']);
            $table->dropColumn('course_element_id');
        });
    }
};
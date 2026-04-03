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
        // Les colonnes n'existent pas dans la table initiale
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('class_group_id')->nullable();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
            $table->foreign('class_group_id')->references('id')->on('class_groups')->onDelete('cascade');
        });
    }
};

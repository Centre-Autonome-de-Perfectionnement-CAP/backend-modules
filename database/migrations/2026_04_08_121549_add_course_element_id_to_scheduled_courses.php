<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_courses', function (Blueprint $table) {
            // On ajoute la colonne et on crée la clé étrangère
            $table->foreignId('course_element_id')
                  ->nullable() 
                  ->after('program_id')
                  ->constrained('course_elements')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_courses', function (Blueprint $table) {
            $table->dropForeign(['course_element_id']);
            $table->dropColumn('course_element_id');
        });
    }
};
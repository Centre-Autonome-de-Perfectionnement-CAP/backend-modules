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
        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->after('id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('class_group_id')->after('academic_year_id')->constrained('class_groups')->onDelete('cascade');
            $table->foreignId('principal_professor_id')->after('professor_id')->constrained('professors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['class_group_id']);
            $table->dropForeign(['principal_professor_id']);
            $table->dropColumn(['academic_year_id', 'class_group_id', 'principal_professor_id']);
        });
    }
};

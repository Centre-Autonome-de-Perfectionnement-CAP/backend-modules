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
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->after('uuid')->constrained('academic_years')->onDelete('cascade');
            $table->dropUnique('program_unique');
            $table->unique(['class_group_id', 'course_element_professor_id', 'academic_year_id'], 'program_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique('program_unique');
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
            $table->unique(['class_group_id', 'course_element_professor_id'], 'program_unique');
        });
    }
};

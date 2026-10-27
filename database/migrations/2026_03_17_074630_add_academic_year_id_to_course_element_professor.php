<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('course_element_professor', 'academic_year_id')) {
            Schema::table('course_element_professor', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                      ->nullable()
                      ->constrained('academic_years')
                      ->onDelete('set null')
                      ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_element_professor', 'academic_year_id')) {
            Schema::table('course_element_professor', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            });
        }
    }
};

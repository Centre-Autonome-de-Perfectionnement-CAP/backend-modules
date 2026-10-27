<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('course_element_professor', 'class_group_id')) {
            Schema::table('course_element_professor', function (Blueprint $table) {
                $table->foreignId('class_group_id')->nullable()->after('academic_year_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_element_professor', 'class_group_id')) {
            Schema::table('course_element_professor', function (Blueprint $table) {
                $table->dropColumn('class_group_id');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('course_element_professor', 'principal_professor_id')) {
            return;
        }

        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->foreignId('principal_professor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->foreignId('principal_professor_id')->nullable(false)->change();
        });
    }
};

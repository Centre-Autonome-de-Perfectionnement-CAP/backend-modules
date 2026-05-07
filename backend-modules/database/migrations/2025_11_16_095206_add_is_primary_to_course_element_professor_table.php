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
        if (!Schema::hasColumn('course_element_professor', 'is_primary')) {
            Schema::table('course_element_professor', function (Blueprint $table) {
                $table->boolean('is_primary')->default(false)->after('professor_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};

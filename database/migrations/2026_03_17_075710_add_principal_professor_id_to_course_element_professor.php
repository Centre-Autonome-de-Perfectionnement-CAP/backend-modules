<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->foreignId('principal_professor_id')->nullable()->after('is_primary');
        });
    }

    public function down(): void
    {
        Schema::table('course_element_professor', function (Blueprint $table) {
            $table->dropColumn('principal_professor_id');
        });
    }
};

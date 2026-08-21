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
        Schema::table('pending_students', function (Blueprint $table) {
            $table->unsignedTinyInteger('initial_wave')->default(1)->after('status');
            $table->boolean('is_updated_by_student')->default(false)->after('initial_wave');
            $table->timestamp('last_student_update_at')->nullable()->after('is_updated_by_student');
            $table->json('student_update_summary')->nullable()->after('last_student_update_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_students', function (Blueprint $table) {
            $table->dropColumn([
                'initial_wave',
                'is_updated_by_student',
                'last_student_update_at',
                'student_update_summary',
            ]);
        });
    }
};

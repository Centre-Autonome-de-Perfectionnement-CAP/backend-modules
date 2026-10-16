<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legacy_students', function (Blueprint $table) {
            $table->string('cycle')->nullable()->after('enrollment_year');
        });
    }

    public function down(): void
    {
        Schema::table('legacy_students', function (Blueprint $table) {
            $table->dropColumn('cycle');
        });
    }
};

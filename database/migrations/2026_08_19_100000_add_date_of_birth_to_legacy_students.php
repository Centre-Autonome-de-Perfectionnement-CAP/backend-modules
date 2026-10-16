<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legacy_students', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('first_name');
        });
    }

    public function down(): void
    {
        Schema::table('legacy_students', function (Blueprint $table) {
            $table->dropColumn('date_of_birth');
        });
    }
};

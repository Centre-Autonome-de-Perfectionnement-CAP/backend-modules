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
            $table->string('status')->default('pending')->after('entry_diploma_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_students', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

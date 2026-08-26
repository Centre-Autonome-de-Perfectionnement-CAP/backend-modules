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
            $table->unsignedSmallInteger('transferred_from_wave')->nullable()->after('initial_wave');
            $table->json('transfer_history')->nullable()->after('transferred_from_wave');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_students', function (Blueprint $table) {
            $table->dropColumn(['transferred_from_wave', 'transfer_history']);
        });
    }
};

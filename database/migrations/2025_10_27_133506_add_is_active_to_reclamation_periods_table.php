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
        Schema::table('reclamation_periods', function (Blueprint $table) {
            if (!Schema::hasColumn('reclamation_periods', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamation_periods', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};

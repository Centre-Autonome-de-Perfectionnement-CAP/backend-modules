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
        Schema::table('professors', function (Blueprint $table) {
            $table->string('nationality', 100)->nullable()->after('email');
            $table->string('profession', 100)->nullable()->after('nationality');
            $table->string('city', 100)->nullable()->after('profession');
            $table->string('district', 100)->nullable()->after('city');
            $table->string('plot_number', 100)->nullable()->after('district');
            $table->string('house_number', 100)->nullable()->after('plot_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professors', function (Blueprint $table) {
           $table->dropColumn([
                'nationality',
                'profession',
                'city',
                'district',
                'plot_number',
                'house_number'
            ]);
        });
    }
};

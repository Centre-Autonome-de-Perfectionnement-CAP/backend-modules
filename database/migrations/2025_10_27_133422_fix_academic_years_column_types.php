<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer et recréer les colonnes avec les bons types
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['year_start', 'year_end']);
        });
        
        Schema::table('academic_years', function (Blueprint $table) {
            $table->integer('year_start')->after('academic_year');
            $table->integer('year_end')->after('year_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['year_start', 'year_end']);
        });
        
        Schema::table('academic_years', function (Blueprint $table) {
            $table->date('year_start')->after('academic_year');
            $table->date('year_end')->after('year_start');
        });
    }
};

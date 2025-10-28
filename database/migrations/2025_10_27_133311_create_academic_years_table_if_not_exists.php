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
        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('academic_year')->unique();
                $table->integer('year_start');
                $table->integer('year_end');
                $table->date('submission_start')->nullable();
                $table->date('submission_end')->nullable();
                $table->boolean('is_current')->default(false);
                $table->uuid('uuid')->unique();
                $table->timestamps();
            });
        } else {
            // Modifier la table si elle existe déjà
            Schema::table('academic_years', function (Blueprint $table) {
                if (!Schema::hasColumn('academic_years', 'year_start')) {
                    $table->integer('year_start')->after('academic_year');
                }
                if (!Schema::hasColumn('academic_years', 'year_end')) {
                    $table->integer('year_end')->after('year_start');
                }
                if (!Schema::hasColumn('academic_years', 'is_current')) {
                    $table->boolean('is_current')->default(false)->after('submission_end');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};

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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('academic_year')->unique();
            $table->string('libelle')->nullable();
            $table->date('year_start');
            $table->date('year_end');
            $table->date('submission_start')->nullable();
            $table->date('submission_end')->nullable();
            $table->date('reclamation_start')->nullable();
            $table->date('reclamation_end')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('academic_year');
            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};

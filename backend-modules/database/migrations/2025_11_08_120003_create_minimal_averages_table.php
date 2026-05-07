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
        Schema::create('minimal_averages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('cycle_id')
                ->constrained('cycles')
                ->onDelete('cascade');
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->onDelete('cascade');
            $table->decimal('minimal_average', 5, 2)
                ->default(12.0)
                ->comment('Minimum average required for validation');
            $table->timestamps();

            // Indexes
            $table->index('cycle_id');
            $table->index('academic_year_id');
            
            // One minimal average per cycle per academic year
            $table->unique(['cycle_id', 'academic_year_id'], 'cycle_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minimal_averages');
    }
};

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
        Schema::create('reclamation_periods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('academic_year_id');
            $table->index('department_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamation_periods');
    }
};

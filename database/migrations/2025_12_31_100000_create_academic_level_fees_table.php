<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_level_fees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('study_level')->nullable(); // 1, 2, 3, PREPA
            $table->decimal('registration_fee', 10, 2)->default(0);
            $table->decimal('uemoa_training_fee', 10, 2)->default(0);
            $table->decimal('non_uemoa_training_fee', 10, 2)->default(0);
            $table->decimal('exempted_training_fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_year_id', 'department_id', 'study_level'], 'unique_level_fee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_level_fees');
    }
};

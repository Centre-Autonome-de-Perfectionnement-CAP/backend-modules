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
        Schema::create('pending_students', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tracking_code')->unique();
            $table->foreignId('personal_information_id')->constrained('personal_information')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('entry_diploma_id')->nullable()->constrained('entry_diplomas')->onDelete('set null');
            $table->string('level')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'withdrawn'])->default('pending');
            $table->json('documents')->nullable();
            $table->string('photo')->nullable();
            $table->enum('cuca_opinion', ['favorable', 'defavorable', 'pending'])->nullable();
            $table->text('cuca_comment')->nullable();
            $table->enum('cuo_opinion', ['favorable', 'defavorable', 'pending'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->enum('sponsorise', ['Oui', 'Non'])->default('Non');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tracking_code');
            $table->index('status');
            $table->index('academic_year_id');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_students');
    }
};

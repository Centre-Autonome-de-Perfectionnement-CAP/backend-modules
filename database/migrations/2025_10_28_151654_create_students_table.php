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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('student_id_number')->unique();
            $table->string('password');
            
            // --- COLONNES REQUISES PAR LE SEEDER ---
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('matricule')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('niveau')->nullable();
            $table->boolean('fingerprint_status')->default(0);
            
            // Clés étrangères
            // Note : Assure-toi que les tables 'departments' et 'academic_years' sont créées AVANT celle-ci
            $table->foreignId('filiere_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour la performance des recherches
            $table->index(['student_id_number', 'matricule']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
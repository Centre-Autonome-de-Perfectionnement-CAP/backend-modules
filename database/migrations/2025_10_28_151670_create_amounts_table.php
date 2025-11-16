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
        Schema::create('amounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Critères d'identification
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->integer('level'); // 1 = Prépa/Licence 1, 2 = Spécialité/Licence 2, 3 = Licence 3, 4 = Master 1, 5 = Master 2
            
            // Frais d'inscription
            $table->decimal('registration_fee', 10, 2)->default(0);
            
            // Frais de formation selon le statut de l'étudiant
            $table->decimal('national_training_fee', 10, 2)->default(0); // Nationaux
            $table->decimal('international_training_fee', 10, 2)->default(0); // Non-nationaux
            $table->decimal('exempted_training_fee', 10, 2)->default(0); // Exonérés
            $table->decimal('sponsored_training_fee', 10, 2)->default(0); // Sponsorisés
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour améliorer les performances
            $table->index('academic_year_id');
            $table->index('department_id');
            $table->index('level');
            
            // Contrainte d'unicité : un seul barème par année/département/niveau
            $table->unique(['academic_year_id', 'department_id', 'level'], 'amounts_unique_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amounts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->enum('ecole', ['CAP', 'EPAC'])->default('CAP');

            // Identité
            $table->string('nom');
            $table->string('prenom');
            $table->enum('civilite', ['Monsieur', 'Madame']);

            // Contact
            $table->string('mail')->unique();
            $table->string('telephone', 30);

            // Situation professionnelle
            $table->string('situation_professionnelle');
            $table->string('autre_situation')->nullable();   // si "Autre"
            $table->string('secteur_emploi');
            $table->string('secteur_professionnel');
            $table->enum('type_emploi', ['Employeur', 'Employe', 'Aucun']);
            $table->string('nom_entreprise')->nullable();    // si Employeur/Employé

            // Parcours académique
            $table->string('annee_entree', 4);
            $table->string('annee_sortie', 4);
            $table->unsignedSmallInteger('promotion');
            $table->string('formation');
            $table->string('autre_formation')->nullable();   // si "Autre"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};

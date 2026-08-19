<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : ajouter la colonne transferred_at à la table contrats.
 *
 * Cette colonne enregistre la date/heure exacte d'envoi de l'e-mail de
 * transfert au professeur. Elle sert de référence pour calculer l'expiration
 * du lien de signature après 72 heures.
 *
 * Pour générer puis exécuter cette migration :
 *   php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            // Ajout après authorization_date pour regrouper les champs de dates
            $table->timestamp('transferred_at')
                  ->nullable()
                  ->after('authorization_date')
                  ->comment('Date/heure d\'envoi de l\'e-mail de transfert — référence pour expiration 72 h');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn('transferred_at');
        });
    }
};

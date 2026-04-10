<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signatures numériques scannées des signataires.
 * Optionnel : activable/désactivable globalement via app_settings.
 * Chaque user autorisé peut avoir UNE signature active à la fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_signatures', function (Blueprint $table) {
            $table->id();

            // Utilisateur propriétaire de la signature
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Chemin de l'image (storage/app/public/signatures/{user_id}/{filename})
            $table->string('file_path')->comment('Chemin relatif dans le disk public');

            // Nom original du fichier uploadé
            $table->string('original_name');

            // Dimensions suggérées pour l'apposition sur le PDF
            $table->integer('width_px')->default(200)->comment('Largeur en pixels pour le PDF');
            $table->integer('height_px')->default(80)->comment('Hauteur en pixels pour le PDF');

            // Signature active ou non (un seul active=true par user)
            $table->boolean('is_active')->default(true);

            // Notes (ex: "Signature avec paraphe", "Signature officielle")
            $table->string('label')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('is_active');
        });

        // Paramètre global : activer les signatures numériques sur les PDFs
        // Inséré dans app_settings (si la table existe) ou à gérer via .env
        // Voir guide d'intégration pour activer/désactiver
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_signatures');
    }
};

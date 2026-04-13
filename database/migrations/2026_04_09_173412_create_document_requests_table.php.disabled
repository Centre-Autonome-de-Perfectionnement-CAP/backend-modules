<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mise à jour de la table document_requests pour supporter :
 *  - Les nouveaux statuts (complement_en_attente, validation_finale, secretaire_da_review,
 *    directrice_adjointe_review, secretaire_directeur_review)
 *  - Le numéro WhatsApp du demandeur
 *  - Les informations de complément de dossier
 *  - Les nouveaux acteurs (secrétaire DA, directrice adjointe, secrétaire directeur)
 *  - Le nouveau workflow : Secrétaire → Comptable → Chef Division → Chef CAP
 *                         → Secrétaire DA → Directrice Adjointe
 *                         → Secrétaire Directeur → Directeur → validation_finale → Secrétaire → ready
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            // Numéro WhatsApp du demandeur (renseigné à la soumission)
            $table->string('demandeur_whatsapp', 20)
                  ->nullable()
                  ->after('email')
                  ->comment('Numéro WhatsApp du demandeur pour notifications');

            // Statut de complément de dossier
            $table->text('complement_message')
                  ->nullable()
                  ->after('secretaire_comment')
                  ->comment('Message envoyé au demandeur pour le complément');

            $table->timestamp('complement_requested_at')
                  ->nullable()
                  ->comment('Quand le complément a été demandé');

            $table->json('complement_files')
                  ->nullable()
                  ->comment('Fichiers ajoutés lors du complément (JSON)');

            $table->timestamp('complement_submitted_at')
                  ->nullable()
                  ->comment('Quand le demandeur a soumis le complément');

            // Commentaire de validation (trace de chaque validation positive)
            $table->text('secretaire_da_comment')->nullable()->after('comptable_comment');
            $table->text('directrice_adjointe_comment')->nullable();
            $table->text('secretaire_directeur_comment')->nullable();
            $table->text('directeur_comment')->nullable();

            // Horodatages des nouveaux acteurs
            $table->timestamp('secretaire_da_reviewed_at')->nullable();
            $table->timestamp('directrice_adjointe_reviewed_at')->nullable();
            $table->timestamp('secretaire_directeur_reviewed_at')->nullable();
            $table->timestamp('directeur_reviewed_at_new')->nullable()
                  ->comment('Séparé de l\'ancien directeur_reviewed_at');

            // IDs des utilisateurs ayant traité
            $table->unsignedBigInteger('processed_by_secretaire_da_id')->nullable();
            $table->unsignedBigInteger('processed_by_directrice_adjointe_id')->nullable();
            $table->unsignedBigInteger('processed_by_secretaire_directeur_id')->nullable();
            $table->unsignedBigInteger('processed_by_directeur_id')->nullable();

            // Quand la validation finale a été confirmée par la secrétaire
            $table->timestamp('validation_finale_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn([
                'demandeur_whatsapp',
                'complement_message',
                'complement_requested_at',
                'complement_files',
                'complement_submitted_at',
                'secretaire_da_comment',
                'directrice_adjointe_comment',
                'secretaire_directeur_comment',
                'directeur_comment',
                'secretaire_da_reviewed_at',
                'directrice_adjointe_reviewed_at',
                'secretaire_directeur_reviewed_at',
                'directeur_reviewed_at_new',
                'processed_by_secretaire_da_id',
                'processed_by_directrice_adjointe_id',
                'processed_by_secretaire_directeur_id',
                'processed_by_directeur_id',
                'validation_finale_at',
            ]);
        });
    }
};

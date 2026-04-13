<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique indélébile de toutes les actions sur chaque demande.
 * Chaque transition (validation, rejet, renvoi, complément...) insère une ligne ici.
 * Cette table est en lecture seule une fois écrite (jamais de UPDATE ni DELETE).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_logs', function (Blueprint $table) {
            $table->id();

            // Lien vers la demande concernée
            $table->foreignId('document_request_id')
                  ->constrained('document_requests')
                  ->onDelete('cascade');

            // Transition : de quel statut vers quel statut
            $table->string('from_status', 60)->nullable()->comment('Statut avant l\'action');
            $table->string('to_status', 60)->comment('Statut après l\'action');

            // Action effectuée (nom de l'action backend : secretaire_accept, chef_cap_sign, etc.)
            $table->string('action', 100)->comment('Nom de l\'action effectuée');

            // Qui a effectué l'action
            $table->unsignedBigInteger('performed_by_id')->nullable()->comment('ID user Laravel');
            $table->string('performed_by_role', 50)->nullable()->comment('Slug du rôle');
            $table->string('performed_by_name', 255)->nullable()->comment('Nom complet de l\'acteur');

            // Commentaire / motif (obligatoire pour les rejets)
            $table->text('comment')->nullable()->comment('Motif de rejet ou commentaire de validation');

            // Métadonnées complémentaires (chef_division_type, signature_type, resend_to...)
            $table->json('meta')->nullable()->comment('Données contextuelles en JSON');

            // Horodatage immuable
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('document_request_id');
            $table->index('action');
            $table->index('performed_by_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique immuable de toutes les actions sur les dossiers.
 *
 * ⚠  Pas d'updated_at. Aucune ligne ne doit jamais être modifiée après insertion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('document_request_id');
            $table->foreign('document_request_id')
                  ->references('id')->on('document_requests')
                  ->onDelete('cascade');

            // Acteur (stocké en dur : résiste à la suppression de l'utilisateur)
            $table->unsignedBigInteger('actor_id')->nullable();  // null = système
            $table->string('actor_name');                        
            $table->string('actor_role', 60);                     // slug : "secretaire", "comptable" …

            // Nature de l'action
            $table->string('action_type', 60);
            /*
             *  validation           – approbation silencieuse
             *  validation_flagged   – approbation avec réserve  ← alerte la secrétaire
             *  rejet_partiel        – renvoi en secretaire_correction
             *  rejet_definitif      – rejet irréversible (→ rejected)
             *  correction           – secrétaire renvoie le dossier corrigé
             *  transmission         – passage neutre au maillon suivant
             *  livraison            – remise du document à l'étudiant
             *  message_envoye       – email automatique envoyé (étudiant ou acteur)
             */

            // Transition d'état
            $table->string('status_before', 60)->nullable();
            $table->string('status_after',  60)->nullable();

            // Commentaire / motif (null pour les validations silencieuses)
            $table->text('comment')->nullable();

            // Horodatage non modifiable
            $table->timestamp('created_at')->useCurrent();

            $table->index('document_request_id');
            $table->index(['document_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_histories');
    }
};

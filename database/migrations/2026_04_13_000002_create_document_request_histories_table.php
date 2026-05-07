<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration CONSOLIDÉE — table document_request_histories
 *
 * Regroupe l'intégralité des migrations suivantes (dans l'ordre d'exécution) :
 *   1. 2026_04_13_000001_create_document_request_histories_table.php
 *      → création initiale de l'historique immuable
 *   2. 2026_04_15_000001_add_flag_and_histories_to_demandes.php
 *      → tentative de recréation avec action_label + FK stricte sur actor_id
 *        (fusionnée ici : on retient la version la plus complète)
 *   3. 2026_04_23_000001_cleanup_document_requests_table.php
 *      → ajout des deux index de performance sur cette table
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * RÈGLES IMMUABILITÉ :
 *   • Pas d'updated_at — aucune ligne ne doit jamais être modifiée après insertion.
 *   • actor_id nullable pour permettre les actions système (null = système).
 *   • actor_name et actor_role sont des snapshots : ils résistent à la suppression
 *     d'un utilisateur.
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * Types d'action reconnus (action_type) :
 *   validation          – approbation silencieuse
 *   validation_flagged  – approbation avec réserve (alerte la secrétaire)
 *   rejet_partiel       – renvoi en secretaire_correction
 *   rejet_definitif     – rejet irréversible (→ rejected)
 *   correction          – secrétaire renvoie le dossier corrigé
 *   transmission        – passage neutre au maillon suivant
 *   livraison           – remise du document à l'étudiant
 *   message_envoye      – email automatique envoyé (étudiant ou acteur)
 *   flag_cleared        – flag de réserve acquitté par la secrétaire
 *   resend              – renvoi du dossier
 *   delivery            – livraison physique du document
 *
 * ⚠  Prérequis :
 *   • La table `document_requests` doit exister (voir migration précédente).
 *   • La table `users` doit exister.
 *   • Cette migration DOIT être exécutée APRÈS celle de document_requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_histories', function (Blueprint $table) {

            // ── Identifiant ────────────────────────────────────────────────
            $table->id();

            // ── Lien vers la demande ───────────────────────────────────────
            $table->unsignedBigInteger('document_request_id');
            $table->foreign('document_request_id')
                  ->references('id')->on('document_requests')
                  ->onDelete('cascade');

            // ── Acteur ────────────────────────────────────────────────────
            // actor_id = null signifie une action système (email automatique, etc.)
            $table->unsignedBigInteger('actor_id')->nullable()
                  ->comment('null = action système');
            $table->foreign('actor_id')
                  ->references('id')->on('users')
                  ->nullOnDelete();

            $table->string('actor_name')
                  ->comment('Snapshot du nom complet — résiste à la suppression de l\'utilisateur');
            $table->string('actor_role', 60)
                  ->comment('Snapshot du slug de rôle : secretaire | comptable | chef_division | chef_cap | sec_da | da | sec_dir | directeur');

            // ── Nature de l'action ────────────────────────────────────────
            $table->string('action_type', 60)
                  ->comment('validation | validation_flagged | rejet_partiel | rejet_definitif | correction | transmission | livraison | message_envoye | flag_cleared | resend | delivery');
            $table->string('action_label')->nullable()
                  ->comment('Libellé lisible de l\'action (ex: "Validé par la secrétaire DA")');

            // ── Transition d'état ──────────────────────────────────────────
            $table->string('status_before', 60)->nullable()
                  ->comment('Statut avant l\'action');
            $table->string('status_after', 60)->nullable()
                  ->comment('Statut après l\'action');

            // ── Commentaire / motif ───────────────────────────────────────
            // null pour les validations silencieuses, obligatoire pour les rejets
            $table->text('comment')->nullable();

            // ── Horodatage immuable ───────────────────────────────────────
            // Pas d'updated_at — l'historique est en lecture seule après insertion
            $table->timestamp('created_at')->useCurrent();

            // ── Index ─────────────────────────────────────────────────────
            // Index de base
            $table->index('document_request_id');
            $table->index(['document_request_id', 'created_at']);

            // Index de performance (ajoutés lors du cleanup 2026_04_23)
            $table->index(
                ['document_request_id', 'actor_role', 'created_at'],
                'drh_request_role_idx'
            );
            $table->index(
                ['actor_id', 'action_type'],
                'drh_actor_action_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_histories');
    }
};

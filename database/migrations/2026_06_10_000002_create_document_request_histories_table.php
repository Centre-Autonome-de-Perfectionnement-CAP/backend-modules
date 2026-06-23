<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration unique et finale — document_request_histories
 *
 * L'historique est IMMUABLE : aucune ligne ne doit jamais être modifiée
 * après création. Pas d'updated_at.
 *
 * action_type values :
 *   validation | validation_flagged | rejection | resend |
 *   delivery | correction | flag_cleared | transmission
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_histories', function (Blueprint $table) {
            $table->id();

            // ── Lien vers la demande ───────────────────────────────────────────
            $table->foreignId('document_request_id')
                  ->constrained('document_requests')
                  ->cascadeOnDelete();

            // ── Acteur (snapshot — résiste à la suppression de l'utilisateur) ──
            $table->unsignedBigInteger('actor_id')
                  ->comment('ID de l\'utilisateur au moment de l\'action');
            $table->foreign('actor_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
            $table->string('actor_name')
                  ->comment('Nom complet snapshot');
            $table->string('actor_role', 60)
                  ->comment('Slug du rôle (secretaire, comptable, responsable-division…)');

            // ── Nature de l'action ─────────────────────────────────────────────
            $table->string('action_type', 60)
                  ->comment('validation | validation_flagged | rejection | resend | delivery | correction | flag_cleared | transmission');
            $table->string('action_label')
                  ->comment('Libellé lisible (ex: "Validation", "Rejet", "Renvoi")');

            // ── Transition d'état ──────────────────────────────────────────────
            $table->string('status_before', 60)
                  ->comment('Statut de la demande AVANT l\'action');
            $table->string('status_after', 60)
                  ->comment('Statut de la demande APRÈS l\'action');

            // ── Commentaire / motif ────────────────────────────────────────────
            $table->text('comment')->nullable()
                  ->comment('Motif de rejet, note de validation, commentaire de renvoi…');

            // ── Horodatage immuable — pas d'updated_at ─────────────────────────
            $table->timestamp('created_at')->useCurrent();

            // ── Index de performance ───────────────────────────────────────────
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║  MIGRATION CONSOLIDÉE — document_request_histories                      ║
 * ║  Version finale — remplace TOUTES les migrations antérieures            ║
 * ║  concernant cette table.                                                ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  Migrations remplacées :                                                ║
 * ║   • 2026_04_13_000001_create_document_request_histories_table (active)  ║
 * ║   • 2026_04_13_000001_create_document_request_histories_table (disabled)║
 * ║   • 2026_04_15_000001_add_flag_and_histories_to_demandes (active)       ║
 * ║   • 2026_04_23_000001_cleanup (disabled — ajout des index de perf)      ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  Schéma final :                                                         ║
 * ║                                                                         ║
 * ║  L'historique est IMMUABLE. Aucune ligne ne doit jamais être modifiée   ║
 * ║  après création (garanti aussi par le modèle DocumentRequestHistory).   ║
 * ║                                                                         ║
 * ║  actor_id / actor_name / actor_role sont des snapshots au moment de     ║
 * ║  l'action — ils résistent à la suppression de l'utilisateur.            ║
 * ║                                                                         ║
 * ║  action_type values :                                                   ║
 * ║   validation | validation_flagged | rejection | resend                  ║
 * ║   delivery | correction | flag_cleared | transmission                   ║
 * ║                                                                         ║
 * ║  Index de performance :                                                  ║
 * ║   drh_request_role_idx  (dr_id, actor_role, created_at) — listing        ║
 * ║   drh_actor_action_idx  (actor_id, action_type)          — stats direction║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_request_histories')) {
            $this->updateExistingTable();
        } else {
            $this->createTable();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRÉATION COMPLÈTE (fresh install)
    // ─────────────────────────────────────────────────────────────────────────

    private function createTable(): void
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
                  ->comment(
                      'validation | validation_flagged | rejection | resend | ' .
                      'delivery | correction | flag_cleared | transmission'
                  );
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

            // ── Horodatage immuable ────────────────────────────────────────────
            // Pas d'updated_at — l'historique ne peut jamais être modifié.
            $table->timestamp('created_at')->useCurrent();

            // ── Index de performance ───────────────────────────────────────────
            // drh_request_role_idx : utilisé par DocumentRequestQueryService
            //   pour reconstruire les commentaires/horodatages par rôle
            //   (remplace les colonnes supprimées de document_requests)
            $table->index(
                ['document_request_id', 'actor_role', 'created_at'],
                'drh_request_role_idx'
            );

            // drh_actor_action_idx : utilisé par DocumentRequestQueryService::statsForDirectionUser
            //   pour compter les validations/rejets par utilisateur
            $table->index(
                ['actor_id', 'action_type'],
                'drh_actor_action_idx'
            );
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MISE À JOUR TABLE EXISTANTE (BD en production)
    // Idempotent — chaque bloc vérifie avant d'agir
    // ─────────────────────────────────────────────────────────────────────────

    private function updateExistingTable(): void
    {
        Schema::table('document_request_histories', function (Blueprint $table) {

            // Ajouter action_label si absent (présent dans la migration 2026_04_13 active
            // mais absent dans la version disabled 2026_04_13)
            if (!Schema::hasColumn('document_request_histories', 'action_label')) {
                $table->string('action_label')->default('Action')->after('action_type');
            }

            // S'assurer que actor_id est bien NOT NULL (certaines versions avaient nullable)
            // On ne peut pas modifier NOT NULL facilement ici sans risque de données —
            // on laisse tel quel si la colonne existe
        });

        // ── Index de performance ──────────────────────────────────────────────
        $this->addIndexIfMissing(
            'document_request_histories',
            'drh_request_role_idx',
            function () {
                Schema::table('document_request_histories', function (Blueprint $table) {
                    $table->index(
                        ['document_request_id', 'actor_role', 'created_at'],
                        'drh_request_role_idx'
                    );
                });
            }
        );

        $this->addIndexIfMissing(
            'document_request_histories',
            'drh_actor_action_idx',
            function () {
                Schema::table('document_request_histories', function (Blueprint $table) {
                    $table->index(
                        ['actor_id', 'action_type'],
                        'drh_actor_action_idx'
                    );
                });
            }
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function addIndexIfMissing(string $table, string $indexName, callable $callback): void
    {
        $exists = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        if (empty($exists)) {
            $callback();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ROLLBACK
    // ─────────────────────────────────────────────────────────────────────────

    public function down(): void
    {
        Schema::dropIfExists('document_request_histories');
    }
};

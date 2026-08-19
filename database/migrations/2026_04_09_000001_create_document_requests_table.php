<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration CONSOLIDÉE — table document_requests
 *
 * Regroupe l'intégralité des migrations suivantes (dans l'ordre d'exécution) :
 *   1. 2026_04_09_173412_create_document_requests_table.php
 *      → ajout des acteurs étendu du workflow (secrétaire DA, directrice adjointe,
 *        secrétaire directeur, directeur), numéro WhatsApp, complément de dossier
 *   2. 2025_01_01_000001_ensure_demandeur_whatsapp_in_document_requests.php
 *      → garantit la présence de demandeur_whatsapp (migration défensive)
 *   3. 2026_04_14_000001_add_has_flag_to_document_requests.php
 *      → flag de validation sous réserve
 *   4. 2026_04_16_100000_add_complement_fields_to_document_requests.php
 *      → complement_pieces_requises (supprimé ensuite par le cleanup)
 *   5. 2026_04_18_000001_add_correction_fields_to_document_requests.php
 *   6. 2026_04_19_000001_add_correction_origin_status_to_document_requests.php
 *      → correction_origin_status (idempotent, même colonne)
 *   7. 2026_04_23_000001_cleanup_document_requests_table.php
 *      → suppression des colonnes obsolètes et ajout d'index de performance
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WORKFLOW FINAL (après cleanup) :
 *   Secrétaire → Comptable → Chef Division → Chef CAP
 *   → Secrétaire DA → Directrice Adjointe
 *   → Secrétaire Directeur → Directeur → validation_finale → Secrétaire → ready
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * ⚠  Prérequis : la table `users` doit exister avant cette migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {

            // ── Identifiant ────────────────────────────────────────────────
            $table->id();

            // ── Demandeur ─────────────────────────────────────────────────
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('demandeur_whatsapp', 25)
                  ->nullable()
                  ->comment('Numéro WhatsApp du demandeur pour notifications');

            // ── Document demandé ──────────────────────────────────────────
            $table->string('type_document');
            $table->string('motif')->nullable();
            $table->unsignedInteger('nombre_exemplaires')->default(1);

            // ── Statut & flag ─────────────────────────────────────────────
            $table->string('status', 60)->default('pending')
                  ->comment(
                      'Valeurs possibles : pending | secretaire_review | comptable_review | ' .
                      'chef_division_review | chef_cap_review | secretaire_da_review | ' .
                      'directrice_adjointe_review | secretaire_directeur_review | ' .
                      'directeur_review | validation_finale | secretaire_correction | ' .
                      'complement_en_attente | ready | rejected'
                  );

            $table->boolean('has_flag')->default(false)
                  ->comment('true = validation sous réserve posée, non encore acquittée par la secrétaire');

            // ── Chef division ─────────────────────────────────────────────
            $table->string('chef_division_type', 50)->nullable()
                  ->comment('formation_distance | formation_continue');

            // ── Complément de dossier ─────────────────────────────────────
            $table->json('complement_files')->nullable()
                  ->comment('Fichiers joints lors du complément (JSON)');
            $table->timestamp('complement_submitted_at')->nullable()
                  ->comment('Date à laquelle le demandeur a soumis le complément');

            // ── Circuit de correction ─────────────────────────────────────
            $table->boolean('is_in_correction_circuit')->default(false)
                  ->comment('true pendant que la secrétaire corrige le dossier');
            $table->string('correction_origin_role', 60)->nullable()
                  ->comment('Slug du rôle qui a déclenché le circuit de correction');
            $table->string('correction_origin_status', 60)->nullable()
                  ->comment('Statut BD au moment du déclenchement du circuit de correction — permet de restituer le dossier exactement là où le workflow s\'était arrêté');

            // ── Acteurs étendus — IDs (FK vers users) ────────────────────
            $table->unsignedBigInteger('processed_by_secretaire_da_id')->nullable();
            $table->unsignedBigInteger('processed_by_directrice_adjointe_id')->nullable();
            $table->unsignedBigInteger('processed_by_secretaire_directeur_id')->nullable();
            $table->unsignedBigInteger('processed_by_directeur_id')->nullable();

            // ── Horodatages des acteurs étendus ───────────────────────────
            $table->timestamp('secretaire_da_reviewed_at')->nullable();
            $table->timestamp('directrice_adjointe_reviewed_at')->nullable();
            $table->timestamp('secretaire_directeur_reviewed_at')->nullable();
            $table->timestamp('directeur_reviewed_at_new')->nullable()
                  ->comment('Horodatage du directeur dans le nouveau workflow étendu');

            // ── Validation finale ─────────────────────────────────────────
            $table->timestamp('validation_finale_at')->nullable()
                  ->comment('Date de la confirmation de validation finale par la secrétaire');

            // ── Timestamps Laravel ────────────────────────────────────────
            $table->timestamps();
            $table->softDeletes();

            // ── Clés étrangères ───────────────────────────────────────────
            $table->foreign('processed_by_secretaire_da_id')
                  ->references('id')->on('users')->nullOnDelete();
            $table->foreign('processed_by_directrice_adjointe_id')
                  ->references('id')->on('users')->nullOnDelete();
            $table->foreign('processed_by_secretaire_directeur_id')
                  ->references('id')->on('users')->nullOnDelete();
            $table->foreign('processed_by_directeur_id')
                  ->references('id')->on('users')->nullOnDelete();

            // ── Index de performance (ajoutés lors du cleanup 2026_04_23) ─
            $table->index(['status', 'updated_at'], 'dr_status_updated_idx');
            $table->index(['chef_division_type', 'status'], 'dr_chef_division_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};

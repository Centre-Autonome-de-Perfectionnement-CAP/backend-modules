<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Stages - Lot 1 : demandes de stage, discussion des dates avec le
 * demandeur, notes de service (initiale + rectificatives), historique,
 * réglages.
 *
 * Toutes les tables sont nouvelles : aucune table existante n'est modifiée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 20)->unique();
            $t->string('status', 30)->index();

            // ── Demandeur (formulaire public) ─────────────────────────────
            $t->string('civilite', 5);
            $t->string('nom', 80);
            $t->string('prenoms', 100);
            $t->date('date_naissance');
            $t->string('lieu_naissance', 100);
            $t->string('nationalite', 60);
            $t->string('etablissement_origine', 150);
            $t->string('filiere_niveau', 150);
            $t->date('date_debut_souhaitee')->nullable();
            $t->string('theme_souhaite', 200)->nullable();
            $t->string('duree_souhaitee', 60);
            $t->string('email', 254)->index();
            $t->string('whatsapp', 30)->index();
            $t->json('files');
            $t->timestamp('consent_at');

            // ── Conditions arrêtées (dernière version en vigueur) ─────────
            $t->string('service', 150)->nullable();
            $t->string('lieu', 150)->nullable();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->json('horaires')->nullable();

            // ── Décision ──────────────────────────────────────────────────
            $t->timestamp('proposal_sent_at')->nullable();
            $t->timestamp('proposal_reminded_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamp('expired_at')->nullable();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();

            $t->timestamps();
            $t->index(['status', 'proposal_sent_at']);
        });

        // Échanges sur les conditions du stage (propositions du responsable,
        // contre-propositions et réponses du demandeur). Jamais modifiés.
        Schema::create('stage_request_proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('author', 12);          // staff | demandeur
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('kind', 12);            // proposal | counter | accept
            $t->string('service', 150)->nullable();
            $t->string('lieu', 150)->nullable();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->json('horaires')->nullable();
            $t->text('message')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['stage_request_id', 'id']);
        });

        // Notes de service émises (initiale puis rectificatives). Le contenu
        // est figé (snapshot) : le PDF pourra toujours être régénéré à
        // l'identique.
        Schema::create('stage_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('number', 40)->unique();
            $t->string('kind', 14);            // initiale | rectificative
            $t->unsignedSmallInteger('version');
            $t->json('snapshot');
            $t->text('motif')->nullable();
            $t->string('pdf_path')->nullable();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('issued_at')->useCurrent();
            $t->index(['stage_request_id', 'version']);
        });

        // Historique non modifiable (traçabilité).
        Schema::create('stage_request_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('action', 40);
            $t->string('from_status', 30)->nullable();
            $t->string('to_status', 30)->nullable();
            $t->string('actor', 12);           // staff | demandeur | systeme
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('comment')->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['stage_request_id', 'id']);
        });

        Schema::create('stage_settings', function (Blueprint $t) {
            $t->string('key', 60)->primary();
            $t->json('value');
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_settings');
        Schema::dropIfExists('stage_request_history');
        Schema::dropIfExists('stage_notes');
        Schema::dropIfExists('stage_request_proposals');
        Schema::dropIfExists('stage_requests');
    }
};

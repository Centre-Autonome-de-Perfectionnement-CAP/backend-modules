<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents et références, avis des stagiaires, notation du stage par les
 * stagiaires, évaluation des stagiaires par les superviseurs.
 *
 *  - stage_documents : fichiers et liens ajoutés par le personnel, visibles
 *    de tous les stagiaires en stage et du personnel ;
 *  - stage_feedbacks : suggestions, plaintes, avis. Anonyme : aucun lien
 *    avec le stagiaire n'est enregistré (stage_request_id nul) ;
 *  - stage_ratings : notation du stage (une par mois et une de fin),
 *    toujours anonyme ; voter_hash (empreinte à clé secrète) empêche
 *    seulement de noter deux fois la même période ;
 *  - stage_evaluations : note de chaque superviseur (barème sur 20,
 *    commentaire obligatoire) ; stage_evaluation_results : moyenne
 *    pondérée, note retenue et validation du responsable de division.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stage_documents')) {
            Schema::create('stage_documents', function (Blueprint $t) {
                $t->id();
                $t->string('title', 200);
                $t->string('description', 500)->nullable();
                $t->string('category', 20)->index();
                $t->string('kind', 4);                  // file | link
                $t->string('path')->nullable();
                $t->string('file_name', 200)->nullable();
                $t->string('mime', 120)->nullable();
                $t->unsignedInteger('size')->nullable();
                $t->string('url', 1000)->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamp('announced_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('stage_feedbacks')) {
            Schema::create('stage_feedbacks', function (Blueprint $t) {
                $t->id();
                $t->string('type', 10);                 // suggestion | plainte | positif | negatif
                $t->string('subject', 20)->index();
                $t->text('message');
                $t->unsignedBigInteger('stage_request_id')->nullable(); // nul : anonyme
                $t->string('path')->nullable();
                $t->string('file_name', 200)->nullable();
                $t->string('mime', 120)->nullable();
                $t->boolean('public_ok')->default(false);   // accepte d'être cité publiquement (sans nom)
                $t->timestamp('published_at')->nullable();  // cité sur la page publique
                $t->timestamp('read_at')->nullable();
                $t->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (!Schema::hasTable('stage_ratings')) {
            Schema::create('stage_ratings', function (Blueprint $t) {
                $t->id();
                $t->string('period', 7);                // AAAA-MM, ou « fin »
                $t->string('voter_hash', 64)->unique();
                $t->unsignedTinyInteger('encadrement');
                $t->unsignedTinyInteger('accueil');
                $t->unsignedTinyInteger('conditions');
                $t->unsignedTinyInteger('organisation');
                $t->unsignedTinyInteger('apprentissage');
                $t->json('supervisors')->nullable();    // { id du superviseur : note }, visible des seuls responsables
                $t->text('comment')->nullable();
                $t->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (!Schema::hasTable('stage_evaluations')) {
            Schema::create('stage_evaluations', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('stage_request_id');
                $t->unsignedBigInteger('user_id')->index();
                $t->boolean('titular')->default(false);
                $t->json('scores');
                $t->decimal('total', 4, 2);
                $t->text('comment');
                $t->text('returned_note')->nullable();  // renvoyée par le responsable pour revoir
                $t->timestamp('returned_at')->nullable();
                $t->timestamps();
                $t->unique(['stage_request_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('stage_evaluation_results')) {
            Schema::create('stage_evaluation_results', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('stage_request_id')->unique();
                $t->timestamp('opened_at')->nullable();     // superviseurs invités à noter
                $t->timestamp('reminded_at')->nullable();
                $t->timestamp('ready_at')->nullable();      // toutes les notes des titulaires reçues
                $t->decimal('average', 5, 2)->nullable();
                $t->decimal('final', 4, 2)->nullable();
                $t->json('criteria')->nullable();
                $t->text('appreciation')->nullable();
                $t->unsignedBigInteger('validated_by')->nullable();
                $t->timestamp('validated_at')->nullable();
                $t->string('pdf_path')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['stage_evaluation_results', 'stage_evaluations', 'stage_ratings', 'stage_feedbacks', 'stage_documents'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

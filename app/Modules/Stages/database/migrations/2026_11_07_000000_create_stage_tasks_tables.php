<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espace tâches du module Stages.
 *  - stage_tasks        : tâche (créée par un superviseur ou par le stagiaire),
 *    dernière remise, revue, publication dans « Travaux publiés » ;
 *  - stage_task_members : stagiaire(s) concerné(s) (un stagiaire ou son binôme) ;
 *  - stage_task_items   : rendu de la dernière remise seulement (fichiers ou liens).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stage_tasks')) {
            Schema::create('stage_tasks', function (Blueprint $t) {
                $t->id();
                $t->string('title', 200);
                $t->text('instructions')->nullable();
                $t->date('due_date')->nullable();
                $t->string('created_by_type', 6);                 // staff | intern
                $t->unsignedBigInteger('created_by_id');          // users.id | stage_requests.id
                $t->string('status', 12)->default('todo');        // todo | submitted | revision | validated
                $t->unsignedInteger('version')->default(0);
                $t->text('message')->nullable();                  // mot du stagiaire avec la dernière remise
                $t->timestamp('submitted_at')->nullable();
                $t->unsignedBigInteger('submitted_by')->nullable();
                $t->text('review_comment')->nullable();
                $t->unsignedBigInteger('reviewed_by')->nullable();
                $t->timestamp('reviewed_at')->nullable();
                $t->timestamp('published_at')->nullable()->index();
                $t->unsignedBigInteger('published_by')->nullable();
                $t->timestamp('announced_at')->nullable();        // publication annoncée (récapitulatif du jour)
                $t->date('reminded_on')->nullable();              // rappel de la veille de l'échéance
                $t->timestamps();
                $t->index(['status', 'due_date']);
            });
        }
        if (!Schema::hasTable('stage_task_members')) {
            Schema::create('stage_task_members', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('task_id');
                $t->unsignedBigInteger('stage_request_id')->index();
                $t->unique(['task_id', 'stage_request_id']);
            });
        }
        if (!Schema::hasTable('stage_task_items')) {
            Schema::create('stage_task_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('task_id')->index();
                $t->string('kind', 5);                            // file | link
                $t->string('name', 255);
                $t->string('path', 255)->nullable();
                $t->string('url', 1000)->nullable();
                $t->string('mime', 120)->nullable();
                $t->unsignedInteger('size')->default(0);
                $t->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_task_items');
        Schema::dropIfExists('stage_task_members');
        Schema::dropIfExists('stage_tasks');
    }
};

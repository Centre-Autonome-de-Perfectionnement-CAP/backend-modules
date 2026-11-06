<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications regroupées du module Stages (lignes effacées une fois envoyées) :
 *  - stage_digest_items       : actions des superviseurs (récapitulatif de
 *    17h45 aux responsables) et commentaires de rapports (un message par
 *    rafale de 10 minutes) ;
 *  - stage_report_broadcasts  : périodes de rapports déjà annoncées à tous
 *    (« période de consultation ouverte », une seule fois par période).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stage_digest_items')) {
            Schema::create('stage_digest_items', function (Blueprint $t) {
                $t->id();
                $t->string('kind', 20);                         // superviseur | report_comment
                $t->unsignedBigInteger('report_id')->nullable(); // commentaire : rapport commenté
                $t->unsignedBigInteger('comment_id')->nullable();
                $t->string('line', 500)->nullable();             // superviseur : action
                $t->timestamp('created_at')->useCurrent();
                $t->index(['kind', 'id']);
            });
        }
        if (!Schema::hasTable('stage_report_broadcasts')) {
            Schema::create('stage_report_broadcasts', function (Blueprint $t) {
                $t->id();
                $t->string('period_key', 20)->unique();
                $t->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_report_broadcasts');
        Schema::dropIfExists('stage_digest_items');
    }
};

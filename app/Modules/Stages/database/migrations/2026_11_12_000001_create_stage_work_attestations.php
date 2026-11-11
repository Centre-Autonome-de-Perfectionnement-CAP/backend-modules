<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attestations de travail (personnes ayant travaillé au CAP, souvent
 * d'anciens stagiaires) : invitation par WhatsApp (lien à usage unique, sans
 * compte), vérification par le responsable, impression et remise par le
 * secrétariat, comme les attestations de stage.
 *
 * Statuts : invitee (lien envoyé) → a_valider → a_imprimer → imprimee
 * (à faire signer) → prete (intéressé prévenu) → remise ; annulee.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stage_work_attestations')) {
            return;
        }
        Schema::create('stage_work_attestations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('stage_request_id')->nullable()->index(); // ancien stagiaire (dossier existant)
            $t->string('status', 12)->index();
            $t->string('phone', 30)->nullable()->index();
            $t->string('email', 254)->nullable();
            $t->string('civilite', 5)->nullable();
            $t->string('nom', 80)->nullable();
            $t->string('prenoms', 100)->nullable();
            $t->string('diplome', 200)->nullable();
            $t->string('nature', 80)->nullable();          // « à titre bénévole »… ; vide : sans mention
            $t->json('fonctions')->nullable();
            $t->json('taches')->nullable();
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->text('appreciation')->nullable();
            $t->json('content')->nullable();               // contenu figé à la validation
            $t->string('token_hash', 64)->nullable()->unique();
            $t->timestamp('expires_at')->nullable();
            $t->unsignedSmallInteger('send_count')->default(0);
            $t->timestamp('last_sent_at')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->string('numero', 60)->nullable()->index();
            $t->date('document_date')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('validated_by')->nullable();
            $t->timestamp('validated_at')->nullable();
            $t->unsignedBigInteger('printed_by')->nullable();
            $t->timestamp('printed_at')->nullable();
            $t->unsignedBigInteger('notified_by')->nullable();
            $t->timestamp('notified_at')->nullable();
            $t->unsignedSmallInteger('notify_count')->default(0);
            $t->date('delivered_on')->nullable();
            $t->unsignedBigInteger('delivered_by')->nullable();
            $t->unsignedBigInteger('cancelled_by')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->string('cancel_reason', 1000)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_work_attestations');
    }
};

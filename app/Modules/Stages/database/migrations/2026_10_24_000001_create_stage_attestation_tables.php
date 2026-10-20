<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Stages — Lot 5 : attestations de stage.
 *
 * Circuit : pré-remplie (J-7) → validée par le responsable → imprimée par le
 * secrétariat (numéro proposé, modifiable) → remise au stagiaire (datée),
 * scan facultatif pour l'archive. Attestation exceptionnelle (responsable,
 * mot de passe + motif) et demande ultérieure du stagiaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_attestations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('kind', 16);                    // normale | exceptionnelle | demande
            $t->string('status', 16)->index();         // a_valider | a_imprimer | imprimee | remise | annulee
            $t->string('numero', 60)->nullable()->index();
            $t->json('overrides')->nullable();         // corrections du responsable avant validation
            $t->json('content')->nullable();           // contenu figé à la validation
            $t->json('eligibility')->nullable();       // conditions au moment de la validation
            $t->string('motif_type', 20)->nullable();  // demande du stagiaire : non_recue | perte | duplicata | autre
            $t->text('motif')->nullable();             // exceptionnelle / demande
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('validated_at')->nullable();
            $t->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('printed_at')->nullable();
            $t->date('delivered_on')->nullable();
            $t->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('delivered_at')->nullable();
            $t->string('scan_path')->nullable();
            $t->string('scan_name', 200)->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('cancelled_at')->nullable();
            $t->text('cancel_reason')->nullable();
            $t->timestamp('reminded_at')->nullable();
            $t->timestamps();
            $t->index(['stage_request_id', 'status']);
        });

        Schema::table('stage_requests', function (Blueprint $t) {
            // Préparation automatique de l'attestation (J-7) déjà faite.
            $t->timestamp('attestation_prepared_at')->nullable()->after('withdrawn_at');
        });
    }

    public function down(): void
    {
        Schema::table('stage_requests', function (Blueprint $t) {
            $t->dropColumn('attestation_prepared_at');
        });
        Schema::dropIfExists('stage_attestations');
    }
};

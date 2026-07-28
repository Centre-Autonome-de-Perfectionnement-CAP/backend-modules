<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration unique et finale — document_requests
 *
 * Workflow (13 statuts) :
 *   submitted → accounting_review → division_manager_review →
 *   cap_manager_review → deputy_director_secretary_review →
 *   deputy_director_review → director_secretary_review →
 *   director_review → secretary_final_review → ready_for_pickup →
 *   picked_up | rejected | secretary_correction
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();

            // ── Identité de la demande ─────────────────────────────────────────
            $table->string('reference')->unique()
                  ->comment('Référence courte générée (ATT-XXXX ou BUL-XXXX)');

            $table->foreignId('student_pending_student_id')
                  ->constrained('student_pending_student')
                  ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                  ->nullable()
                  ->constrained('academic_years')
                  ->cascadeOnDelete();

            $table->string('type', 50)
                  ->comment('attestation_passage | attestation_definitive | attestation_inscription | bulletin_annuel');

            // ── Workflow ───────────────────────────────────────────────────────
            $table->enum('status', [
                'submitted',
                'secretary_correction',
                'accounting_review',
                'division_manager_review',
                'cap_manager_review',
                'deputy_director_secretary_review',
                'deputy_director_review',
                'director_secretary_review',
                'director_review',
                'secretary_final_review',
                'ready_for_pickup',
                'picked_up',
                'rejected',
            ])->default('submitted');

            // ── Réserve (flag) ─────────────────────────────────────────────────
            $table->boolean('has_flag')->default(false)
                  ->comment('true = une validation sous réserve est active et non levée');

            // ── Rejet ──────────────────────────────────────────────────────────
            $table->text('rejected_reason')->nullable();
            $table->string('rejected_by')->nullable()
                  ->comment('Libellé du rôle ayant effectué le rejet définitif');

            // ── Signature Direction ────────────────────────────────────────────
            $table->enum('signature_type', ['paraphe', 'signature'])->nullable()
                  ->comment('Type de validation apposé par le Directeur');

            // ── Responsable Division ───────────────────────────────────────────
            $table->enum('responsable_division_type', ['formation_distance', 'formation_continue'])
                  ->nullable()
                  ->comment('Déterminé automatiquement depuis le cycle de l\'étudiant');

            // ── Circuit de correction (navette secrétaire ↔ acteurs) ───────────
            $table->boolean('is_in_correction_circuit')->default(false)
                  ->comment('true = le dossier circule en navette entre la secrétaire et les acteurs');
            $table->string('correction_origin_role')->nullable()
                  ->comment('Slug du rôle ayant déclenché le circuit');
            $table->string('correction_origin_status')->nullable()
                  ->comment('Statut BD au moment du déclenchement du circuit');

            // ── Contact demandeur ──────────────────────────────────────────────
            $table->string('email')->nullable();
            $table->string('demandeur_whatsapp', 25)->nullable()
                  ->comment('Numéro WhatsApp normalisé E.164 (ex: +22997123456)');

            // ── Paiement ───────────────────────────────────────────────────────
            $table->enum('payment_method', ['manual', 'tresor_online'])->default('manual')
                  ->comment('manual = quittance physique | tresor_online = paiement API Trésor Public');
            $table->string('payment_reference', 50)->nullable()
                  ->comment('N° quittance Trésor (préfixe QUI-TRESO- si paiement en ligne)');

            // ── Fichiers ───────────────────────────────────────────────────────
            $table->json('files')->nullable()
                  ->comment('Pièces déposées à la soumission initiale (demande_manuscrite, acte_naissance…)');
            $table->json('complement_files')->nullable()
                  ->comment('Pièces complémentaires déposées après soumission');
            $table->json('secretary_files')->nullable()
                  ->comment('Fichiers attachés par la secrétaire');

            // ── Métadonnées ────────────────────────────────────────────────────
            $table->string('department_name')->nullable()
                  ->comment('Snapshot du département à la soumission');

            // ── Horodatages ────────────────────────────────────────────────────
            $table->timestamp('submitted_at')->nullable()
                  ->comment('Quand la demande a été soumise par l\'étudiant');
            $table->timestamp('complement_at')->nullable()
                  ->comment('Quand le dernier complément a été soumis');
            $table->timestamp('delivered_at')->nullable()
                  ->comment('Quand le document a été remis physiquement à l\'étudiant');
            $table->timestamps();

            // ── Index ──────────────────────────────────────────────────────────
            $table->index(['status', 'updated_at'], 'dr_status_updated_idx');
            $table->index(['responsable_division_type', 'status'], 'dr_responsable_division_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};

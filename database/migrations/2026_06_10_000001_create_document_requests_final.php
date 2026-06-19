<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║  MIGRATION CONSOLIDÉE — document_requests                               ║
 * ║  Version finale — remplace TOUTES les migrations antérieures            ║
 * ║  concernant cette table.                                                ║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  Migrations remplacées (toutes désactivées via .disabled) :             ║
 * ║   • 2026_03_10_135126_create_document_requests_table                    ║
 * ║   • 2026_04_09_173412_create_document_requests_table (disabled)         ║
 * ║   • 2025_01_01_000001_ensure_demandeur_whatsapp (disabled)              ║
 * ║   • 2026_04_14_000001_add_has_flag (disabled)                           ║
 * ║   • 2026_04_16_100000_add_complement_fields (disabled)                  ║
 * ║   • 2026_04_18_000001_add_correction_fields (disabled)                  ║
 * ║   • 2026_04_19_000001_add_correction_origin_status (disabled)           ║
 * ║   • 2026_04_23_000001_cleanup (disabled)                                ║
 * ║   • 2026_05_20_120000_rename_status_slugs (disabled)                    ║
 * ║   • 2026_05_20_135919_remove_secretary_review_enum (disabled)           ║
 * ║   • 2026_05_23_104910_add_secretary_files (disabled)                    ║
 * ║   • 2026_06_01_000001_add_secretary_final_review (disabled)             ║
 * ║   • 2026_06_03_000001_rename_chef_division_type (active → remplacé)     ║
 * ║   • 2026_06_03_000002_rename_chef_division_data (active → remplacé)     ║
 * ║   • 2026_06_03_000003_rename_chef_division_role_name (active → remplacé)║
 * ╠══════════════════════════════════════════════════════════════════════════╣
 * ║  Schéma final de la table document_requests :                           ║
 * ║                                                                         ║
 * ║  Workflow :                                                              ║
 * ║   status (ENUM) — 13 valeurs :                                          ║
 * ║     submitted → accounting_review → division_manager_review →           ║
 * ║     cap_manager_review → deputy_director_secretary_review →             ║
 * ║     deputy_director_review → director_secretary_review →                ║
 * ║     director_review → secretary_final_review → ready_for_pickup →       ║
 * ║     picked_up / rejected / secretary_correction                         ║
 * ║                                                                         ║
 * ║  Circuit de correction :                                                 ║
 * ║   is_in_correction_circuit, correction_origin_role,                     ║
 * ║   correction_origin_status                                              ║
 * ║                                                                         ║
 * ║  Fichiers :                                                              ║
 * ║   files (pièces originales), complement_files, secretary_files          ║
 * ║                                                                         ║
 * ║  Paiement :                                                              ║
 * ║   payment_method (manual|tresor_online), payment_reference              ║
 * ║                                                                         ║
 * ║  Notifications :                                                         ║
 * ║   email, demandeur_whatsapp (E.164)                                     ║
 * ║                                                                         ║
 * ║  Acteurs :                                                               ║
 * ║   responsable_division_type (formation_distance|formation_continue)     ║
 * ║   signature_type (paraphe|signature)                                    ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Si la table existe déjà (BD en ligne), on la met à jour ───────────
        // ── Si elle n'existe pas (fresh install), on la crée ──────────────────

        if (Schema::hasTable('document_requests')) {
            $this->updateExistingTable();
        } else {
            $this->createTable();
        }

        // ── Données : renommage chef-division → responsable-division ──────────
        $this->renameChefdivisionToResponsableDivision();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRÉATION COMPLÈTE (fresh install)
    // ─────────────────────────────────────────────────────────────────────────

    private function createTable(): void
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
                  ->comment('attestation_passage | attestation_definitive | attestation_inscription | bulletin_*');

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
                'secretary_final_review',       // après signature Directeur, avant remise
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

            // ── Responsable Division (auto-détecté au passage chez le Comptable) ─
            $table->enum('responsable_division_type', ['formation_distance', 'formation_continue'])
                  ->nullable()
                  ->comment('Déterminé automatiquement depuis le cycle de l\'étudiant');

            // ── Circuit de correction (navette secrétaire ↔ acteurs) ───────────
            $table->boolean('is_in_correction_circuit')->default(false)
                  ->comment('true = le dossier circule en navette entre la secrétaire et les acteurs');
            $table->string('correction_origin_role')->nullable()
                  ->comment('Slug du rôle ayant déclenché le circuit (ex: comptable, chef-cap)');
            $table->string('correction_origin_status')->nullable()
                  ->comment('Statut BD au moment du déclenchement : permet la sortie exacte du circuit');

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
                  ->comment('Fichiers attachés par la secrétaire (ex: document finalisé)');

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
            $table->timestamps(); // created_at + updated_at

            // ── Index ──────────────────────────────────────────────────────────
            $table->index(['status', 'updated_at'],              'dr_status_updated_idx');
            $table->index(['responsable_division_type', 'status'], 'dr_responsable_division_type_idx');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MISE À JOUR TABLE EXISTANTE (BD en production)
    // Chaque bloc vérifie l'existence avant d'agir — idempotent
    // ─────────────────────────────────────────────────────────────────────────

    private function updateExistingTable(): void
    {
        // 1. Mettre à jour l'ENUM status (version finale avec secretary_final_review)
        DB::statement("ALTER TABLE document_requests MODIFY status VARCHAR(100) NOT NULL DEFAULT 'submitted'");
        DB::statement("ALTER TABLE document_requests MODIFY status ENUM(
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
            'rejected'
        ) NOT NULL DEFAULT 'submitted'");

        Schema::table('document_requests', function (Blueprint $table) {

            // ── Colonnes potentiellement absentes ────────────────────────────

            if (!Schema::hasColumn('document_requests', 'has_flag')) {
                $table->boolean('has_flag')->default(false)->after('status');
            }

            if (!Schema::hasColumn('document_requests', 'is_in_correction_circuit')) {
                $table->boolean('is_in_correction_circuit')->default(false);
            }

            if (!Schema::hasColumn('document_requests', 'correction_origin_role')) {
                $table->string('correction_origin_role')->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'correction_origin_status')) {
                $table->string('correction_origin_status')->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'demandeur_whatsapp')) {
                $table->string('demandeur_whatsapp', 25)->nullable()->after('email');
            }

            if (!Schema::hasColumn('document_requests', 'payment_method')) {
                $table->enum('payment_method', ['manual', 'tresor_online'])->default('manual');
            }

            if (!Schema::hasColumn('document_requests', 'payment_reference')) {
                $table->string('payment_reference', 50)->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'complement_files')) {
                $table->json('complement_files')->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'secretary_files')) {
                $table->json('secretary_files')->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'complement_at')) {
                $table->timestamp('complement_at')->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'department_name')) {
                $table->string('department_name')->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'responsable_division_type')) {
                // Cas : colonne chef_division_type existe → renommer
                if (Schema::hasColumn('document_requests', 'chef_division_type')) {
                    $table->renameColumn('chef_division_type', 'responsable_division_type');
                } else {
                    $table->enum('responsable_division_type', ['formation_distance', 'formation_continue'])
                          ->nullable();
                }
            }

            if (!Schema::hasColumn('document_requests', 'signature_type')) {
                $table->enum('signature_type', ['paraphe', 'signature'])->nullable();
            }

            if (!Schema::hasColumn('document_requests', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
        });

        // ── Supprimer les colonnes obsolètes (si présentes depuis migrations antérieures)
        $obsoleteColumns = [
            'secretaire_comment', 'comptable_comment', 'chef_division_comment',
            'secretaire_da_comment', 'directrice_adjointe_comment',
            'secretaire_directeur_comment', 'directeur_comment',
            'comptable_reviewed_at', 'chef_division_reviewed_at', 'chef_cap_reviewed_at',
            'sec_da_reviewed_at', 'secretaire_da_reviewed_at',
            'directrice_adjointe_reviewed_at', 'sec_directeur_reviewed_at',
            'directeur_reviewed_at', 'directeur_reviewed_at_new',
            'processed_by_secretaire_id', 'processed_by_comptable_id',
            'processed_by_chef_division_id', 'processed_by_chef_cap_id',
            'processed_by_secretaire_da_id', 'processed_by_directrice_adjointe_id',
            'processed_by_secretaire_directeur_id', 'processed_by_directeur_id',
            'complement_message', 'complement_pieces_requises',
            'complement_requested_at', 'complement_submitted_at',
            'unavailable_reason', 'validation_finale_at',
        ];

        // Supprimer FK avant de dropper les colonnes
        $foreignsToDrop = [
            'document_requests_processed_by_secretaire_id_foreign',
            'document_requests_processed_by_comptable_id_foreign',
            'document_requests_processed_by_chef_division_id_foreign',
            'document_requests_processed_by_chef_cap_id_foreign',
        ];

        foreach ($foreignsToDrop as $fk) {
            $exists = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_NAME = 'document_requests'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                  AND CONSTRAINT_NAME = ?
            ", [$fk]);
            if (!empty($exists)) {
                DB::statement("ALTER TABLE document_requests DROP FOREIGN KEY `{$fk}`");
            }
        }

        $presentObsolete = array_filter(
            $obsoleteColumns,
            fn($col) => Schema::hasColumn('document_requests', $col)
        );

        if (!empty($presentObsolete)) {
            Schema::table('document_requests', function (Blueprint $table) use ($presentObsolete) {
                $table->dropColumn(array_values($presentObsolete));
            });
        }

        // ── Index ────────────────────────────────────────────────────────────
        $this->addIndexIfMissing('document_requests', 'dr_status_updated_idx', function () {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->index(['status', 'updated_at'], 'dr_status_updated_idx');
            });
        });

        // Nettoyer l'ancien index chef_division si présent
        $this->dropIndexIfExists('document_requests', 'dr_chef_division_type_idx');

        $this->addIndexIfMissing('document_requests', 'dr_responsable_division_type_idx', function () {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->index(['responsable_division_type', 'status'], 'dr_responsable_division_type_idx');
            });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RENOMMAGE DONNÉES chef-division → responsable-division
    // Idempotent : ne fait rien si déjà renommé
    // ─────────────────────────────────────────────────────────────────────────

    private function renameChefdivisionToResponsableDivision(): void
    {
        // 1. Table roles
        DB::table('roles')
            ->where('slug', 'chef-division')
            ->update(['slug' => 'responsable-division', 'name' => 'Responsable Division']);

        // 2. Historiques
        if (Schema::hasTable('document_request_histories')) {
            DB::table('document_request_histories')
                ->where('actor_role', 'chef-division')
                ->update(['actor_role' => 'responsable-division']);
        }

        // 3. Circuit de correction
        DB::table('document_requests')
            ->where('correction_origin_role', 'chef-division')
            ->update(['correction_origin_role' => 'responsable-division']);
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

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        if (!empty($exists)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ROLLBACK
    // ─────────────────────────────────────────────────────────────────────────

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};

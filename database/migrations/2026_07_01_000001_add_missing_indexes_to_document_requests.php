<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CORRECTIF (v2) — B2.2 : Indexes manquants sur document_requests
 *
 * Toutes les colonnes ci-dessous sont confirmées présentes dans le
 * modèle/migrations réels (student_pending_student_id, type, status,
 * created_at, is_in_correction_circuit, responsable_division_type) —
 * vérifiées via DocumentRequest, DocumentRequestQueryService et
 * TransitionService réels.
 *
 * Requêtes fréquentes identifiées dans le code source réel qui justifient
 * ces index :
 *
 *   1. DemandeController / ComplementDossierController (vérification doublon) :
 *        WHERE student_pending_student_id = ? AND type = ?
 *          AND status NOT IN ('rejected','picked_up')
 *      → index composite (student_pending_student_id, type, status)
 *
 *   2. DocumentRequestQueryService::listing() :
 *        WHERE status IN (...) ORDER BY created_at ASC
 *      → index composite (status, created_at)
 *
 *   3. DocumentRequestQueryService::listing() — filtre Responsable Division :
 *        WHERE responsable_division_type = ?
 *      → index simple (responsable_division_type) — utile uniquement
 *        combiné à status, donc inclus dans l'index composite ci-dessous
 *
 *   4. TransitionService::assertActionAllowed() — vérification circuit
 *      de correction, lue à chaque transition :
 *        is_in_correction_circuit (déjà filtré en mémoire après lecture
 *        par id — pas de gain à indexer seul, mais utile combiné à status
 *        pour d'éventuels rapports futurs)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {

            if (!$this->indexExists('document_requests', 'dr_sps_type_status_idx')) {
                $table->index(['student_pending_student_id', 'type', 'status'], 'dr_sps_type_status_idx');
            }

            if (!$this->indexExists('document_requests', 'dr_status_created_idx')) {
                $table->index(['status', 'created_at'], 'dr_status_created_idx');
            }

            if (!$this->indexExists('document_requests', 'dr_responsable_division_type_idx')) {
                $table->index(['responsable_division_type', 'status'], 'dr_responsable_division_type_idx');
            }

            if (!$this->indexExists('document_requests', 'dr_correction_circuit_idx')) {
                $table->index(['is_in_correction_circuit', 'status'], 'dr_correction_circuit_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropIndexIfExists('dr_sps_type_status_idx');
            $table->dropIndexIfExists('dr_status_created_idx');
            $table->dropIndexIfExists('dr_responsable_division_type_idx');
            $table->dropIndexIfExists('dr_correction_circuit_idx');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $dbName     = $connection->getDatabaseName();
        $driver     = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $count = $connection->selectOne(
                "SELECT COUNT(*) as cnt FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$dbName, $table, $indexName]
            );
            return ($count->cnt ?? 0) > 0;
        }

        return false; // SQLite (tests)
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLUMNS_TO_DROP = [
        // Commentaires
        'secretaire_comment',
        'comptable_comment',
        'chef_division_comment',
        'secretaire_da_comment',
        'directrice_adjointe_comment',
        'secretaire_directeur_comment',
        'directeur_comment',

        // Reviewed_at
        'comptable_reviewed_at',
        'chef_division_reviewed_at',
        'chef_cap_reviewed_at',
        'sec_da_reviewed_at',
        'secretaire_da_reviewed_at',
        'directrice_adjointe_reviewed_at',
        'sec_directeur_reviewed_at',
        'directeur_reviewed_at',

        // FK agents
        'processed_by_secretaire_id',
        'processed_by_comptable_id',
        'processed_by_chef_division_id',
        'processed_by_chef_cap_id',

        // Colonnes inutiles
        'complement_message',
        'complement_pieces_requises',
        'complement_requested_at',
        'unavailable_reason',
    ];

    public function up(): void
    {
        // 🔥 1. Drop FK proprement (AVANT Schema::table)
        $foreigns = [
            'document_requests_processed_by_secretaire_id_foreign',
            'document_requests_processed_by_comptable_id_foreign',
            'document_requests_processed_by_chef_division_id_foreign',
            'document_requests_processed_by_chef_cap_id_foreign',
        ];

        foreach ($foreigns as $fk) {
            $exists = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_NAME = 'document_requests'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME = ?
            ", [$fk]);

            if (!empty($exists)) {
                DB::statement("ALTER TABLE document_requests DROP FOREIGN KEY `$fk`");
            }
        }

        // 🔥 2. Détecter les colonnes existantes AVANT le Schema::table
        $existing = array_filter(self::COLUMNS_TO_DROP, function ($col) {
            return Schema::hasColumn('document_requests', $col);
        });

        // 🔥 3. Drop colonnes
        if (!empty($existing)) {
            Schema::table('document_requests', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }

        // 🔥 4. Index histories (perf)
        if (!$this->indexExists('document_request_histories', 'drh_request_role_idx')) {
            Schema::table('document_request_histories', function (Blueprint $table) {
                $table->index(
                    ['document_request_id', 'actor_role', 'created_at'],
                    'drh_request_role_idx'
                );
            });
        }

        if (!$this->indexExists('document_request_histories', 'drh_actor_action_idx')) {
            Schema::table('document_request_histories', function (Blueprint $table) {
                $table->index(
                    ['actor_id', 'action_type'],
                    'drh_actor_action_idx'
                );
            });
        }

        // 🔥 5. Index document_requests
        if (!$this->indexExists('document_requests', 'dr_status_updated_idx')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->index(['status', 'updated_at'], 'dr_status_updated_idx');
            });
        }

        if (!$this->indexExists('document_requests', 'dr_chef_division_type_idx')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->index(['chef_division_type', 'status'], 'dr_chef_division_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {

            // Commentaires
            $table->text('secretaire_comment')->nullable();
            $table->text('comptable_comment')->nullable();
            $table->text('chef_division_comment')->nullable();
            $table->text('secretaire_da_comment')->nullable();
            $table->text('directrice_adjointe_comment')->nullable();
            $table->text('secretaire_directeur_comment')->nullable();
            $table->text('directeur_comment')->nullable();

            // Reviewed_at
            $table->timestamp('comptable_reviewed_at')->nullable();
            $table->timestamp('chef_division_reviewed_at')->nullable();
            $table->timestamp('chef_cap_reviewed_at')->nullable();
            $table->timestamp('sec_da_reviewed_at')->nullable();
            $table->timestamp('secretaire_da_reviewed_at')->nullable();
            $table->timestamp('directrice_adjointe_reviewed_at')->nullable();
            $table->timestamp('sec_directeur_reviewed_at')->nullable();
            $table->timestamp('directeur_reviewed_at')->nullable();

            // FK agents
            $table->unsignedBigInteger('processed_by_secretaire_id')->nullable();
            $table->unsignedBigInteger('processed_by_comptable_id')->nullable();
            $table->unsignedBigInteger('processed_by_chef_division_id')->nullable();
            $table->unsignedBigInteger('processed_by_chef_cap_id')->nullable();

            // Colonnes inutiles
            $table->text('complement_message')->nullable();
            $table->json('complement_pieces_requises')->nullable();
            $table->timestamp('complement_requested_at')->nullable();
            $table->text('unavailable_reason')->nullable();
        });

        // Drop index
        Schema::table('document_request_histories', function (Blueprint $table) {
            try { $table->dropIndex('drh_request_role_idx'); } catch (\Throwable) {}
            try { $table->dropIndex('drh_actor_action_idx'); } catch (\Throwable) {}
        });

        Schema::table('document_requests', function (Blueprint $table) {
            try { $table->dropIndex('dr_status_updated_idx'); } catch (\Throwable) {}
            try { $table->dropIndex('dr_chef_division_type_idx'); } catch (\Throwable) {}
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return !empty($indexes);
        } catch (\Throwable) {
            return false;
        }
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B2.7 — Vérification des clés étrangères
 *
 * ⚠️ AVERTISSEMENT IMPORTANT (transparence) :
 * Le zip fourni ne contenait PAS les fichiers de migration originaux
 * (database/migrations/*.php) pour document_requests et
 * document_request_histories — uniquement les Models et Services.
 * Je ne peux donc pas confirmer avec certitude si ces FK existent déjà
 * ou non en base réelle.
 *
 * Cette migration est donc fournie À TITRE DE VÉRIFICATION SEULEMENT,
 * protégée par fkExists() qui n'ajoute rien si la contrainte est déjà
 * présente. Avant de l'exécuter en production :
 *
 *   1. Vérifiez d'abord avec :
 *        SHOW CREATE TABLE document_request_histories;
 *        SHOW CREATE TABLE document_requests;
 *      pour voir les FK déjà déclarées.
 *
 *   2. Si actor_id pointe vers une table autre que `users` dans votre
 *      schéma réel, ADAPTEZ la référence ci-dessous avant exécution.
 *
 *   3. Vérifiez l'absence de lignes orphelines avant d'ajouter la FK
 *      document_request_id (CASCADE) :
 *        SELECT COUNT(*) FROM document_request_histories h
 *        LEFT JOIN document_requests dr ON h.document_request_id = dr.id
 *        WHERE dr.id IS NULL;
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_request_histories', function (Blueprint $table) {
            if (Schema::hasColumn('document_request_histories', 'document_request_id')
                && !$this->fkExists('document_request_histories', 'document_request_id')) {
                $table->foreign('document_request_id')
                      ->references('id')->on('document_requests')
                      ->onDelete('cascade');
            }

            if (Schema::hasColumn('document_request_histories', 'actor_id')
                && !$this->fkExists('document_request_histories', 'actor_id')) {
                $table->foreign('actor_id')
                      ->references('id')->on('users')
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_request_histories', function (Blueprint $table) {
            $table->dropForeignIfExists('document_request_id');
            $table->dropForeignIfExists('actor_id');
        });
    }

    private function fkExists(string $table, string $column): bool
    {
        $connection = Schema::getConnection();
        $dbName     = $connection->getDatabaseName();
        $driver     = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $count = $connection->selectOne(
                "SELECT COUNT(*) as cnt FROM information_schema.key_column_usage
                 WHERE table_schema = ? AND table_name = ? AND column_name = ?
                   AND referenced_table_name IS NOT NULL",
                [$dbName, $table, $column]
            );
            return ($count->cnt ?? 0) > 0;
        }

        return false;
    }
};

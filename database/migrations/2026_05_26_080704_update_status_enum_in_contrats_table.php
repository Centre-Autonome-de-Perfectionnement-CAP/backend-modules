<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration : ajouter 'resiliated' à l'ENUM status de la table contrats
 * ET ajouter la colonne transferred_at si elle n'existe pas encore.
 *
 * Exécuter avec : php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Modifier l'ENUM status pour inclure 'resiliated' ──────────────
        DB::statement("
            ALTER TABLE contrats
            MODIFY COLUMN status ENUM(
                'pending',
                'transfered',
                'signed',
                'ongoing',
                'completed',
                'cancelled',
                'resiliated'
            ) NOT NULL DEFAULT 'pending'
        ");

        // ── 2. Ajouter transferred_at si elle n'existe pas encore ────────────
        $columns = DB::select("SHOW COLUMNS FROM contrats LIKE 'transferred_at'");
        if (empty($columns)) {
            DB::statement("
                ALTER TABLE contrats
                ADD COLUMN transferred_at TIMESTAMP NULL DEFAULT NULL
                AFTER authorization_date
                COMMENT 'Date/heure envoi email transfert — référence expiration 72h'
            ");
        }
    }

    public function down(): void
    {
        // ── Retirer 'resiliated' de l'ENUM ───────────────────────────────────
        // D'abord remettre les éventuels contrats résiliés en 'cancelled'
        DB::statement("
            UPDATE contrats SET status = 'cancelled' WHERE status = 'resiliated'
        ");

        DB::statement("
            ALTER TABLE contrats
            MODIFY COLUMN status ENUM(
                'pending',
                'transfered',
                'signed',
                'ongoing',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");

        // ── Supprimer transferred_at ─────────────────────────────────────────
        $columns = DB::select("SHOW COLUMNS FROM contrats LIKE 'transferred_at'");
        if (!empty($columns)) {
            DB::statement("ALTER TABLE contrats DROP COLUMN transferred_at");
        }
    }
};

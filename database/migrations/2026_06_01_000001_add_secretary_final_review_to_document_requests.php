<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute le statut 'secretary_final_review' à la colonne status de document_requests.
 *
 * Nouveau workflow :
 *   director_review → (directeur_sign) → secretary_final_review
 *                                       → (secretaire_mark_ready) → ready_for_pickup
 *
 * La secrétaire est désormais la seule à pouvoir passer un dossier en ready_for_pickup
 * après signature du Directeur.
 */
return new class extends Migration
{
    private const NEW_STATUSES = [
        'submitted',
        'secretary_correction',
        'accounting_review',
        'division_manager_review',
        'cap_manager_review',
        'deputy_director_secretary_review',
        'deputy_director_review',
        'director_secretary_review',
        'director_review',
        'secretary_final_review',   // ← NOUVEAU
        'ready_for_pickup',
        'picked_up',
        'rejected',
    ];

    private const OLD_STATUSES = [
        'submitted',
        'secretary_correction',
        'accounting_review',
        'division_manager_review',
        'cap_manager_review',
        'deputy_director_secretary_review',
        'deputy_director_review',
        'director_secretary_review',
        'director_review',
        'ready_for_pickup',
        'picked_up',
        'rejected',
    ];

    public function up(): void
    {
        // 1. Passer en VARCHAR pour éviter les contraintes ENUM pendant la modification
        DB::statement("ALTER TABLE document_requests MODIFY status VARCHAR(100) NOT NULL DEFAULT 'submitted'");

        // 2. Redéfinir l'ENUM avec le nouveau statut
        $enumDef = "ENUM(" . implode(",", array_map(fn($v) => "'{$v}'", self::NEW_STATUSES)) . ")";
        DB::statement("ALTER TABLE document_requests MODIFY status {$enumDef} NOT NULL DEFAULT 'submitted'");

        // Même chose pour correction_origin_status (VARCHAR, pas d'ENUM — rien à faire)
    }

    public function down(): void
    {
        // Repasser en VARCHAR d'abord
        DB::statement("ALTER TABLE document_requests MODIFY status VARCHAR(100) NOT NULL DEFAULT 'submitted'");

        // Remettre les dossiers en secretary_final_review vers director_review (rollback logique)
        DB::table('document_requests')
            ->where('status', 'secretary_final_review')
            ->update(['status' => 'director_review']);

        // Redéfinir l'ENUM sans le nouveau statut
        $enumDef = "ENUM(" . implode(",", array_map(fn($v) => "'{$v}'", self::OLD_STATUSES)) . ")";
        DB::statement("ALTER TABLE document_requests MODIFY status {$enumDef} NOT NULL DEFAULT 'submitted'");
    }
};

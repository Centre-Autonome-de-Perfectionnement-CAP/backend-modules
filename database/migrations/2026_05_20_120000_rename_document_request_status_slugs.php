<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MAPPING = [
        'pending'                    => 'submitted',
        'secretaire_review'          => 'submitted',
        'secretaire_correction'      => 'secretary_correction',
        'comptable_review'           => 'accounting_review',
        'chef_division_review'       => 'division_manager_review',
        'chef_cap_review'            => 'cap_manager_review',
        'sec_dir_adjointe_review'    => 'deputy_director_secretary_review',
        'directrice_adjointe_review' => 'deputy_director_review',
        'sec_directeur_review'       => 'director_secretary_review',
        'directeur_review'           => 'director_review',
        'ready'                      => 'ready_for_pickup',
        'delivered'                  => 'picked_up',
        'rejected'                   => 'rejected',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temporairement convertir la colonne ENUM en VARCHAR pour éviter les erreurs de truncation MySQL/MariaDB
        DB::statement("ALTER TABLE document_requests MODIFY status VARCHAR(100) NOT NULL");

        // 2. Mettre à jour toutes les valeurs de statuts
        foreach (self::MAPPING as $old => $new) {
            if ($old === $new) {
                continue;
            }

            // document_requests.status
            DB::table('document_requests')
                ->where('status', $old)
                ->update(['status' => $new]);

            // document_requests.correction_origin_status
            DB::table('document_requests')
                ->where('correction_origin_status', $old)
                ->update(['correction_origin_status' => $new]);

            // document_request_histories.status_before
            DB::table('document_request_histories')
                ->where('status_before', $old)
                ->update(['status_before' => $new]);

            // document_request_histories.status_after
            DB::table('document_request_histories')
                ->where('status_after', $old)
                ->update(['status_after' => $new]);
        }

        // 3. Re-convertir la colonne status en ENUM avec les nouveaux statuts et la nouvelle valeur par défaut 'submitted'
        $newEnumDefinition = "ENUM(" . implode(",", array_map(fn($val) => "'$val'", array_values(self::MAPPING))) . ")";
        DB::statement("ALTER TABLE document_requests MODIFY status {$newEnumDefinition} NOT NULL DEFAULT 'submitted'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Temporairement convertir la colonne ENUM en VARCHAR
        DB::statement("ALTER TABLE document_requests MODIFY status VARCHAR(100) NOT NULL");

        // 2. Restaurer toutes les anciennes valeurs de statuts
        foreach (self::MAPPING as $old => $new) {
            if ($old === $new) {
                continue;
            }

            // document_requests.status
            DB::table('document_requests')
                ->where('status', $new)
                ->update(['status' => $old]);

            // document_requests.correction_origin_status
            DB::table('document_requests')
                ->where('correction_origin_status', $new)
                ->update(['correction_origin_status' => $old]);

            // document_request_histories.status_before
            DB::table('document_request_histories')
                ->where('status_before', $new)
                ->update(['status_before' => $old]);

            // document_request_histories.status_after
            DB::table('document_request_histories')
                ->where('status_after', $new)
                ->update(['status_after' => $old]);
        }

        // 3. Re-convertir la colonne status en ENUM avec les anciens statuts et l'ancienne valeur par défaut 'pending'
        $oldEnumDefinition = "ENUM(" . implode(",", array_map(fn($val) => "'$val'", array_keys(self::MAPPING))) . ")";
        DB::statement("ALTER TABLE document_requests MODIFY status {$oldEnumDefinition} NOT NULL DEFAULT 'pending'");
    }
};

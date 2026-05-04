<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne correction_origin_status à document_requests.
 *
 * Pourquoi ce fichier existe :
 *   La migration 2026_04_18_000001 avait ajouté correction_origin_role et
 *   is_in_correction_circuit, mais avait oublié correction_origin_status.
 *   Le TransitionService écrit dans cette colonne à chaque rejet → erreur 500.
 *   Ce fichier a un nom différent (04_19) donc Laravel l'exécutera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('document_requests', 'correction_origin_status')) {
                $table->string('correction_origin_status')
                      ->nullable()
                      ->after('is_in_correction_circuit')
                      ->comment('Statut BD au moment du déclenchement du circuit de correction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            if (Schema::hasColumn('document_requests', 'correction_origin_status')) {
                $table->dropColumn('correction_origin_status');
            }
        });
    }
};

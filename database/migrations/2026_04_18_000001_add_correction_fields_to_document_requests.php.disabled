<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne correction_origin_status manquante.
 *
 * Contexte :
 *   La migration 2026_04_18_000001 avait créé correction_origin_role et
 *   is_in_correction_circuit, mais avait oublié correction_origin_status.
 *   Le TransitionService tente d'écrire dans cette colonne lors de tout rejet
 *   → erreur 500 DATABASE_ERROR.
 *
 * correction_origin_status : statut BD au moment du déclenchement du circuit
 *   (ex: 'comptable_review'). Utilisé lors de la sortie du circuit pour
 *   restituer le dossier exactement là où le workflow s'était arrêté.
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
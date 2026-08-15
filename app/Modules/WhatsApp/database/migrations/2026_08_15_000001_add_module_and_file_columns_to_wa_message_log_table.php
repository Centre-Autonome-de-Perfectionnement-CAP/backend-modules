<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à wa_message_log :
 *   - module      : détecté AUTOMATIQUEMENT par WhatsAppBridgeClient (namespace
 *                   de l'appelant, ex: 'Demandes', 'Attestation', 'RH'...) —
 *                   aucun module n'a besoin de le renseigner lui-même.
 *   - file_name   : nom du fichier joint, si envoi de document/image.
 *   - media_type  : 'text' | 'image' | 'document' — pour l'affichage admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_message_log', function (Blueprint $table) {
            if (!Schema::hasColumn('wa_message_log', 'module')) {
                $table->string('module')->nullable()->after('context')->index();
            }
            if (!Schema::hasColumn('wa_message_log', 'file_name')) {
                $table->string('file_name')->nullable()->after('module');
            }
            if (!Schema::hasColumn('wa_message_log', 'media_type')) {
                $table->string('media_type')->default('text')->after('file_name');
            }
            // Nécessaire pour que le RETRY d'un message avec fichier fonctionne
            // même après expiration du token temporaire (10 min) — on peut
            // toujours régénérer une URL fraîche à partir de disk+path.
            if (!Schema::hasColumn('wa_message_log', 'file_disk')) {
                $table->string('file_disk')->nullable()->after('media_type');
            }
            if (!Schema::hasColumn('wa_message_log', 'file_path')) {
                $table->string('file_path')->nullable()->after('file_disk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wa_message_log', function (Blueprint $table) {
            $table->dropColumn(['module', 'file_name', 'media_type', 'file_disk', 'file_path']);
        });
    }
};

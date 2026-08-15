<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table wa_sessions — stockage des credentials Baileys en base de données.
 *
 * Remplace le stockage filesystem (auth/<session-id>/*.json).
 * Single-tenant : une seule ligne exploitée en pratique (session_id fixe
 * "primary-session"), mais la table reste une table normale (pas de
 * verrou "1 seule ligne" imposé en DB) pour rester simple à faire évoluer.
 *
 * Chiffrement : AES-256-GCM. On stocke séparément l'IV, le tag
 * d'authentification et les données chiffrées — nécessaire pour pouvoir
 * déchiffrer (GCM exige le tag pour vérifier l'intégrité avant déchiffrement).
 * Clé de chiffrement : WA_SESSION_ENCRYPTION_KEY (backend-modules/.env),
 * jamais stockée en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_sessions')) {
            return;
        }

        Schema::create('wa_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique()->default('primary-session');
            $table->text('creds_iv')->nullable();
            $table->text('creds_tag')->nullable();
            $table->longText('creds_data')->nullable();
            $table->string('phone')->nullable();
            $table->string('display_name')->nullable();
            $table->string('status')->default('disconnected'); // disconnected|connecting|connected
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_sessions');
    }
};

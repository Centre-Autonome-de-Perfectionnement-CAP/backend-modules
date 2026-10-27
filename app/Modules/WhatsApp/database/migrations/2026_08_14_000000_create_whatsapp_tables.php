<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module WhatsApp : toutes ses tables, en une seule migration.
 *
 * Chaque étape ne s'exécute que si elle manque : une base neuve reçoit tout,
 * une base qui avait déjà passé les anciennes migrations du module (serveur
 * de test) n'est pas modifiée.
 *
 *  - wa_sessions     : identifiants de la session Baileys (une ligne,
 *                      "primary-session").
 *  - wa_session_keys : clés Signal de Baileys, une ligne par (session, type,
 *                      id) - sans elles, le chiffrement avec chaque contact
 *                      serait renégocié à chaque redémarrage.
 *  - wa_message_log  : historique de tous les envois (succès ou échec), lu par
 *                      l'administration (envoyés, échoués, statistiques,
 *                      renvoi).
 *
 * Chiffrement AES-256-GCM : IV, tag et données stockés séparément (GCM exige
 * le tag pour vérifier l'intégrité). Clé : WA_SESSION_ENCRYPTION_KEY (.env),
 * jamais stockée en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wa_sessions')) {
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

        if (!Schema::hasTable('wa_session_keys')) {
            Schema::create('wa_session_keys', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->default('primary-session');
                $table->string('key_type');   // ex: 'pre-key', 'session', 'sender-key'
                $table->string('key_id');     // identifiant de la clé dans ce type
                $table->text('value_iv');
                $table->text('value_tag');
                $table->longText('value_data');
                $table->timestamps();

                $table->unique(['session_id', 'key_type', 'key_id'], 'wa_session_keys_unique');
                $table->index(['session_id', 'key_type'], 'wa_session_keys_type_idx');
            });
        }

        if (!Schema::hasTable('wa_message_log')) {
            Schema::create('wa_message_log', function (Blueprint $table) {
                $table->id();
                $table->string('recipient');           // numéro normalisé (E.164, sans +)
                $table->text('message');
                $table->string('context')->nullable(); // ex: "soumission:REF-001"
                $table->string('status')->default('queued'); // queued|sending|sent|failed
                $table->unsignedInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('recipient');
            });
        }

        // module : détecté automatiquement par WhatsAppBridgeClient (namespace
        // de l'appelant). file_disk + file_path : permettent de renvoyer un
        // message avec fichier même après expiration du lien temporaire.
        Schema::table('wa_message_log', function (Blueprint $table) {
            if (!Schema::hasColumn('wa_message_log', 'module')) {
                $table->string('module')->nullable()->after('context')->index();
            }
            if (!Schema::hasColumn('wa_message_log', 'file_name')) {
                $table->string('file_name')->nullable()->after('module');
            }
            if (!Schema::hasColumn('wa_message_log', 'media_type')) {
                $table->string('media_type')->default('text')->after('file_name'); // text | image | document
            }
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
        Schema::dropIfExists('wa_message_log');
        Schema::dropIfExists('wa_session_keys');
        Schema::dropIfExists('wa_sessions');
    }
};

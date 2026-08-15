<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table wa_session_keys — magasin de clés Signal utilisé par Baileys
 * (pre-key, session, sender-key, app-state-sync-key, app-state-sync-version,
 * sender-key-memory...).
 *
 * useMultiFileAuthState stockait UN FICHIER par (type, id) dans auth/.
 * On reproduit exactement la même granularité en DB : une ligne par
 * (session_id, key_type, key_id), chiffrée individuellement en AES-256-GCM.
 *
 * C'est indispensable : sans ce magasin, seuls les creds de premier niveau
 * seraient persistés et le chiffrement de session avec chaque contact
 * WhatsApp devrait être renégocié à chaque redémarrage (voire échouerait).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_session_keys')) {
            return;
        }

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

    public function down(): void
    {
        Schema::dropIfExists('wa_session_keys');
    }
};

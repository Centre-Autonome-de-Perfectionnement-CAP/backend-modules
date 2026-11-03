<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Téléphone de stage ouvert depuis plusieurs applications du même
 * téléphone (navigateur, application installée sur l'écran d'accueil,
 * autre navigateur) : sur iPhone, chacune a son propre stockage, donc son
 * propre identifiant d'appareil. Chaque identifiant confirmé par l'empreinte
 * du téléphone de stage est rattaché à sa clé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stage_webauthn_device_links')) {
            return;
        }
        Schema::create('stage_webauthn_device_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credential_id')->constrained('stage_webauthn_credentials')->cascadeOnDelete();
            $t->string('device_hash', 64)->index();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['credential_id', 'device_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_webauthn_device_links');
    }
};

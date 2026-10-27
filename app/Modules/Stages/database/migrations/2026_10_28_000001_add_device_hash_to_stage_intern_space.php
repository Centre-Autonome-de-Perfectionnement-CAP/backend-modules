<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Téléphone de stage : l'appareil enregistré à l'activation est le seul qui
 * ouvre l'espace du stagiaire (empreinte ET identifiant d'appareil, conservé
 * haché). Les sessions sont liées à cet appareil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_webauthn_credentials', function (Blueprint $t) {
            $t->string('device_hash', 64)->nullable()->index()->after('device_label');
        });
        Schema::table('stage_intern_sessions', function (Blueprint $t) {
            $t->string('device_hash', 64)->nullable()->after('ip');
        });
    }

    public function down(): void
    {
        Schema::table('stage_webauthn_credentials', fn (Blueprint $t) => $t->dropColumn('device_hash'));
        Schema::table('stage_intern_sessions', fn (Blueprint $t) => $t->dropColumn('device_hash'));
    }
};

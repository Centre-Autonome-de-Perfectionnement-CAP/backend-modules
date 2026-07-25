<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Table de paramètres globaux de l'application.
 * Permet d'activer/désactiver des fonctionnalités depuis l'admin sans toucher au code.
 *
 * Clés initiales insérées :
 *  - digital_signatures_enabled : active les signatures numériques sur les PDFs
 *  - whatsapp_notifications_enabled : active les notifications WhatsApp
 *  - mail_notifications_enabled : active les notifications mail workflow
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clé du paramètre');
            $table->text('value')->nullable()->comment('Valeur (string, boolean as 0/1, JSON)');
            $table->string('type')->default('string')->comment('string|boolean|json|integer');
            $table->string('label')->comment('Libellé lisible pour l\'admin');
            $table->text('description')->nullable();
            $table->string('group')->default('general')->comment('Groupe pour l\'UI admin');
            $table->timestamps();
        });

        // Valeurs par défaut
        DB::table('app_settings')->insert([
            [
                'key'         => 'digital_signatures_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Signatures numériques actives',
                'description' => 'Si activé, les signatures numériques scannées seront apposées sur les PDFs générés.',
                'group'       => 'signatures',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'whatsapp_notifications_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'label'       => 'Notifications WhatsApp actives',
                'description' => 'Si activé, des messages WhatsApp seront envoyés via Twilio à chaque transition.',
                'group'       => 'notifications',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'mail_notifications_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'label'       => 'Notifications email actives',
                'description' => 'Si activé, des emails seront envoyés à chaque transition du workflow.',
                'group'       => 'notifications',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'twilio_account_sid',
                'value'       => '',
                'type'        => 'string',
                'label'       => 'Twilio Account SID',
                'description' => 'Identifiant de compte Twilio pour les SMS/WhatsApp.',
                'group'       => 'twilio',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'twilio_auth_token',
                'value'       => '',
                'type'        => 'string',
                'label'       => 'Twilio Auth Token',
                'description' => 'Token d\'authentification Twilio.',
                'group'       => 'twilio',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'key'         => 'twilio_whatsapp_from',
                'value'       => 'whatsapp:+14155238886',
                'type'        => 'string',
                'label'       => 'Numéro WhatsApp Twilio (expéditeur)',
                'description' => 'Numéro WhatsApp Twilio sandbox ou production. Format: whatsapp:+14155238886',
                'group'       => 'twilio',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table wa_message_log — historique de tous les envois WhatsApp.
 *
 * Alimentée par le Node à chaque appel POST /send-message (succès ou échec).
 * Consultée par Laravel (WhatsAppAdminController) pour les onglets
 * "Messages envoyés" / "Messages échoués" / "Statistiques" / "Retry".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_message_log')) {
            return;
        }

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

    public function down(): void
    {
        Schema::dropIfExists('wa_message_log');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messagerie de stage, suite : réponse à un message, pièces jointes (effacées
 * avec leur message après 7 jours) et heure de lecture (« vu par »).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stage_chat_messages', 'reply_to_id')) {
            Schema::table('stage_chat_messages', function (Blueprint $t) {
                $t->unsignedBigInteger('reply_to_id')->nullable()->after('body');
            });
        }
        if (!Schema::hasColumn('stage_chat_reads', 'read_at')) {
            Schema::table('stage_chat_reads', function (Blueprint $t) {
                $t->timestamp('read_at')->nullable()->after('last_read_id'); // dernière avancée de lecture
            });
        }
        if (!Schema::hasTable('stage_chat_files')) {
            Schema::create('stage_chat_files', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('message_id')->index();
                $t->string('path', 255);
                $t->string('name', 150);
                $t->string('mime', 100);
                $t->unsignedInteger('size');
                $t->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_chat_files');
        if (Schema::hasColumn('stage_chat_reads', 'read_at')) {
            Schema::table('stage_chat_reads', fn (Blueprint $t) => $t->dropColumn('read_at'));
        }
        if (Schema::hasColumn('stage_chat_messages', 'reply_to_id')) {
            Schema::table('stage_chat_messages', fn (Blueprint $t) => $t->dropColumn('reply_to_id'));
        }
    }
};

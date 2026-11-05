<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messagerie de stage (texte seulement, éphémère : messages effacés après
 * 7 jours). Deux tables propres au module ; rien d'autre n'est modifié.
 *  - stage_chat_messages : messages d'une discussion (« general » ou
 *    « groupe-{id du superviseur} ») ;
 *  - stage_chat_reads    : où chacun en est (dernier message lu), et les
 *    rappels WhatsApp déjà envoyés (un seul par rafale, deux par jour au plus).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stage_chat_messages')) {
            Schema::create('stage_chat_messages', function (Blueprint $t) {
                $t->id();
                $t->string('channel', 40);
                $t->string('sender_type', 6);                 // staff | intern
                $t->unsignedBigInteger('sender_id');          // users.id | stage_requests.id
                $t->text('body');
                $t->timestamp('created_at')->useCurrent()->index();
                $t->index(['channel', 'id']);
            });
        }

        if (!Schema::hasTable('stage_chat_reads')) {
            Schema::create('stage_chat_reads', function (Blueprint $t) {
                $t->id();
                $t->string('channel', 40);
                $t->string('reader_type', 6);
                $t->unsignedBigInteger('reader_id');
                $t->unsignedBigInteger('last_read_id')->default(0);
                $t->timestamp('seen_at')->nullable();             // dernière consultation (lecture en cours)
                $t->unsignedBigInteger('notified_for_id')->nullable(); // 1er message non lu déjà signalé
                $t->date('notified_day')->nullable();
                $t->unsignedTinyInteger('notified_count')->default(0);
                $t->timestamp('updated_at')->nullable();
                $t->unique(['channel', 'reader_type', 'reader_id']);
                $t->index(['reader_type', 'reader_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_chat_reads');
        Schema::dropIfExists('stage_chat_messages');
    }
};

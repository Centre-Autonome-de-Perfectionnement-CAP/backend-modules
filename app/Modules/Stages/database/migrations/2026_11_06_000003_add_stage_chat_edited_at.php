<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Messagerie : un message peut être modifié par son auteur (« modifié »). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stage_chat_messages') && !Schema::hasColumn('stage_chat_messages', 'edited_at')) {
            Schema::table('stage_chat_messages', function (Blueprint $t) {
                $t->timestamp('edited_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stage_chat_messages', 'edited_at')) {
            Schema::table('stage_chat_messages', function (Blueprint $t) {
                $t->dropIndex(['edited_at']);
                $t->dropColumn('edited_at');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Superviseurs membres du groupe WhatsApp annuel des stagiaires. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_whatsapp_group_staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('group_id')->constrained('stage_whatsapp_groups')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('added_at')->nullable();
            $t->timestamp('invite_sent_at')->nullable();
            $t->timestamp('removed_at')->nullable();
            $t->timestamps();
            $t->unique(['group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_whatsapp_group_staff');
    }
};

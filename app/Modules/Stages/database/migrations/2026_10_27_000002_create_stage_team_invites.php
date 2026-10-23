<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Binôme proposé par un stagiaire à un autre depuis son espace (référence
 * de stage) : l'invité accepte ou refuse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_team_invites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('from_stage_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->foreignId('to_stage_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('status', 12)->default('pending'); // pending | accepted | declined | cancelled
            $t->timestamp('expires_at');
            $t->timestamp('responded_at')->nullable();
            $t->timestamps();
            $t->index(['to_stage_id', 'status']);
            $t->index(['from_stage_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_team_invites');
    }
};

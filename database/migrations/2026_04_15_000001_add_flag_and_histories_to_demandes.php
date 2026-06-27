<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 2. Table historique ───────────────────────────────────────────────
        Schema::create('document_request_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')
                  ->constrained('document_requests')
                  ->cascadeOnDelete();
            $table->foreignId('actor_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('actor_name');           // snapshot — résiste à suppression user
            $table->string('actor_role');           // snapshot du slug rôle
            $table->string('action_type');          // validation | validation_flagged | rejection | resend | delivery | correction | flag_cleared
            $table->string('action_label');         // libellé lisible
            $table->string('status_before');
            $table->string('status_after');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Pas d'updated_at — l'historique est immuable
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_histories');
    }
};

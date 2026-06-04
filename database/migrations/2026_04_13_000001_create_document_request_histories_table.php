<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained('document_requests')->cascadeOnDelete();
            
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('actor_name');
            $table->string('actor_role');
            
            $table->string('action_type');
            $table->string('action_label');
            
            $table->string('status_before');
            $table->string('status_after');
            
            $table->text('comment')->nullable();
            
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['document_request_id', 'actor_role', 'created_at'], 'drh_request_role_idx');
            $table->index(['actor_id', 'action_type'], 'drh_actor_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_histories');
    }
};

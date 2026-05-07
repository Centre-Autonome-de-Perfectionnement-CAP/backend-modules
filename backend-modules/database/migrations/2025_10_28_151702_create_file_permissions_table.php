<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permission_type', ['read', 'write', 'delete', 'share'])->default('read');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('file_id');
            $table->index('user_id');
            $table->unique(['file_id', 'user_id', 'permission_type'], 'file_user_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_permissions');
    }
};

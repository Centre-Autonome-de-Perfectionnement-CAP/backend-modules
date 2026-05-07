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
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('share_token')->unique()->nullable();
            $table->enum('share_type', ['user', 'public', 'link'])->default('user');
            $table->boolean('can_download')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('file_id');
            $table->index('shared_by');
            $table->index('share_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_shares');
    }
};

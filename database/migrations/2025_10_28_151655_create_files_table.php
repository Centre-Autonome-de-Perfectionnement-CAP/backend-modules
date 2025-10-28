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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('extension');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('visibility', ['public', 'private', 'shared'])->default('private');
            $table->string('collection')->nullable();
            $table->string('module_name')->nullable();
            $table->string('module_resource_type')->nullable();
            $table->unsignedBigInteger('module_resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('module_name');
            $table->index(['module_resource_type', 'module_resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('important_informations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('icon')->default('bi-info-circle');
            $table->enum('color', ['primary', 'success', 'info', 'warning', 'danger'])->default('primary');
            $table->string('link')->nullable();
            $table->foreignId('file_id')->nullable()->constrained('files')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('important_informations');
    }
};

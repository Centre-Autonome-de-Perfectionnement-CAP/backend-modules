<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('important_information_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('important_information_id')->constrained('important_informations')->onDelete('cascade');
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique(['important_information_id', 'file_id']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('important_information_file');
    }
};

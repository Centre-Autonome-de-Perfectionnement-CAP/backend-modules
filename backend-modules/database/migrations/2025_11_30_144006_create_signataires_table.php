<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signataires', function (Blueprint $table) {
            $table->id();
            $table->enum('titre', ['Chef CAP', 'Directeur']);
            $table->string('nom');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signataires');
    }
};

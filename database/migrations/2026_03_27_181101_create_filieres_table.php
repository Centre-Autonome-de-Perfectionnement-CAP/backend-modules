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
        Schema::create('filieres', function (Blueprint $table) {
            $table->id(); // clé primaire
            $table->uuid('uuid')->unique(); // identifiant unique
            $table->string('code')->unique(); // code court (ex: GCIV, INFO)
            $table->string('name'); // nom complet (ex: Génie civil)
            $table->text('description')->nullable(); // optionnel
            $table->timestamps(); // created_at et updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filieres');
    }
};

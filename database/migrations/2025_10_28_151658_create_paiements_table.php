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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->string('matricule');
            $table->decimal('montant', 10, 2);
            $table->string('numero_compte')->nullable();
            $table->date('date_versement');
            $table->string('motif')->nullable();
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->enum('statut', ['en_attente', 'accepte', 'rejete'])->default('en_attente');
            $table->text('observation')->nullable();
            $table->foreignId('quittance_id')->nullable()->constrained('files')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('reference');
            $table->index('matricule');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};

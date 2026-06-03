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
        Schema::table('attendances', function (Blueprint $blueprint) {
            // Option A : Si tu veux que ce soit une chaîne de caractères classique
            $blueprint->string('late_type')->nullable()->change();
            
            // Option B : Si tu veux garder un ENUM mais uniquement avec 'retard'
            // $blueprint->enum('late_type', ['retard'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $blueprint) {
            // Revenir à l'état initial en cas de rollback
            $blueprint->enum('late_type', ['leger', 'grave'])->nullable()->change();
        });
    }
};
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
        // On vérifie si la colonne n'existe pas avant de tenter de l'ajouter
        if (!Schema::hasColumn('students', 'phone')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('phone')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('students', 'phone')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
<<<<<<< HEAD
        // La colonne program_id n'existe pas dans la table amounts
=======
        Schema::table('amounts', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable()->change();
        });
>>>>>>> 7854261 (commit)
    }

    public function down(): void
    {
<<<<<<< HEAD
        // Rien à faire
=======
        Schema::table('amounts', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable(false)->change();
        });
>>>>>>> 7854261 (commit)
    }
};

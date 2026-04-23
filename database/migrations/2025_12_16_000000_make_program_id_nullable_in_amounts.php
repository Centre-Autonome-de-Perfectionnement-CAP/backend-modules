<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La colonne program_id n'existe pas dans la table amounts
    }

    public function down(): void
    {
        // Rien à faire
    }
};

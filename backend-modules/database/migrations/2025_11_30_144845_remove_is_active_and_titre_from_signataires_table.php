<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signataires', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'titre']);
        });
    }

    public function down(): void
    {
        Schema::table('signataires', function (Blueprint $table) {
            $table->enum('titre', ['Chef CAP', 'Directeur'])->after('id');
            $table->boolean('is_active')->default(true)->after('nom');
        });
    }
};

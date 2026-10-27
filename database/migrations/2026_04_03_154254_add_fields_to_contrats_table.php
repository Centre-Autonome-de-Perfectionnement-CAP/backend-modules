<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            // Motif de rejet saisi par le professeur lors du rejet du contrat
            $table->text('rejection_reason')->nullable()->after('notes');

            // Indicateur d'autorisation du contrat (déclenchée par l'admin après validation)
            $table->boolean('is_authorized')->default(false)->after('is_validated');
            $table->timestamp('authorization_date')->nullable()->after('is_authorized');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'is_authorized', 'authorization_date']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter seulement ce qui n'existe pas
        if (!Schema::hasColumn('document_requests', 'complement_pieces_requises')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->json('complement_pieces_requises')
                      ->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_requests', 'complement_pieces_requises')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->dropColumn('complement_pieces_requises');
            });
        }
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            // ── PDF final autorisé ────────────────────────────────────────────
            // Chemin du PDF final stocké (peut être le PDF généré ou uploadé par l'admin)
            $table->string('pdf_path')->nullable()->after('professor_signed_at');

            // Date à laquelle le PDF a été uploadé / généré
            $table->timestamp('pdf_uploaded_at')->nullable()->after('pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'pdf_uploaded_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : Ajout de la signature électronique sur la table contrats
 *
 * Champs ajoutés :
 *   - professor_signature_path  : chemin vers l'image de signature (stockée dans storage/app/public/signatures/)
 *   - professor_signature_type  : 'drawn' (dessinée à la main) | 'uploaded' (fichier image uploadé)
 *   - professor_signed_at       : horodatage de la signature
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->string('professor_signature_path')->nullable()->after('rejection_reason');
            $table->string('professor_signature_type')->nullable()->after('professor_signature_path'); // 'drawn' | 'uploaded'
            $table->timestamp('professor_signed_at')->nullable()->after('professor_signature_type');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn([
                'professor_signature_path',
                'professor_signature_type',
                'professor_signed_at',
            ]);
        });
    }
};

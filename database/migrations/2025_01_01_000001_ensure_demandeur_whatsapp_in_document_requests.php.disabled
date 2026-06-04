<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vérifie l'existence de demandeur_whatsapp dans document_requests.
 *
 * Migration défensive : n'ajoute la colonne QUE si elle est absente.
 * Ne plante pas si elle existe déjà.
 *
 * Exécuter : php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_requests', 'demandeur_whatsapp')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->string('demandeur_whatsapp', 25)->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_requests', 'demandeur_whatsapp')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->dropColumn('demandeur_whatsapp');
            });
        }
    }
};

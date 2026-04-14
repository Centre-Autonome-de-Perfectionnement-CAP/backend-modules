<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le champ has_flag à la table document_requests.
 *
 * has_flag = true  → une validation sous réserve a été posée et n'a pas encore
 *                     été acquittée par la secrétaire.
 * has_flag = false → état normal (aucun flag actif ou flag acquitté).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->boolean('has_flag')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn('has_flag');
        });
    }
};

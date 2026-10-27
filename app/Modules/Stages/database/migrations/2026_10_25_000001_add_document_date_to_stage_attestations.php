<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attestation : date portée sur le document (« Abomey-Calavi, le … »),
 * choisie à l'impression (par défaut le jour même).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_attestations', function (Blueprint $t) {
            $t->date('document_date')->nullable()->after('printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('stage_attestations', function (Blueprint $t) {
            $t->dropColumn('document_date');
        });
    }
};

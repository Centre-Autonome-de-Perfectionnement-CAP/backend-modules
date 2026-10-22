<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le cachet numérique (code de vérification + QR) est abandonné au profit de
 * la signature numérique du RD / FC-P : la colonne est retirée là où elle
 * avait été créée.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stage_notes', 'verify_code')) {
            Schema::table('stage_notes', function (Blueprint $t) {
                $t->dropUnique(['verify_code']);
                $t->dropColumn('verify_code');
            });
        }
    }

    public function down(): void
    {
        // Rien à recréer : le cachet numérique n'existe plus.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rapport final corrigé après la soutenance : une nouvelle version peut être
 * déposée après la validation (et même après l'attestation). Elle est relue
 * sans remettre en cause la validation ; la dernière version validée fait foi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_reports', function (Blueprint $t) {
            $t->unsignedSmallInteger('official_version')->nullable()->after('current_version');
            $t->string('correction_status', 12)->nullable()->after('status'); // pending | approved | rejected
        });
        Schema::table('stage_report_versions', function (Blueprint $t) {
            $t->boolean('post_defense')->default(false)->after('late');
        });

        // Rapports déjà validés : la version validée fait foi.
        DB::table('stage_reports')->whereIn('status', ['approved', 'supervisor_approved'])
            ->update(['official_version' => DB::raw('current_version')]);
    }

    public function down(): void
    {
        Schema::table('stage_report_versions', function (Blueprint $t) {
            $t->dropColumn('post_defense');
        });
        Schema::table('stage_reports', function (Blueprint $t) {
            $t->dropColumn(['official_version', 'correction_status']);
        });
    }
};

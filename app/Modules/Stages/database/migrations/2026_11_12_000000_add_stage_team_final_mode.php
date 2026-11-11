<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Binômes :
 *  - stage_teams.final_mode : rapport final « individuel » (chacun le sien,
 *    par défaut) ou « commun » (un même mémoire, déposé une fois pour les deux) ;
 *  - stage_intern_invitations : invitation en binôme (les deux liens sont
 *    reliés ; le binôme se forme quand le second s'inscrit).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stage_teams') && !Schema::hasColumn('stage_teams', 'final_mode')) {
            Schema::table('stage_teams', function (Blueprint $t) {
                $t->string('final_mode', 10)->default('individuel')->after('name');
            });
        }
        if (Schema::hasTable('stage_intern_invitations') && !Schema::hasColumn('stage_intern_invitations', 'pair_id')) {
            Schema::table('stage_intern_invitations', function (Blueprint $t) {
                $t->unsignedBigInteger('pair_id')->nullable()->after('phone')->index();
                $t->string('final_mode', 10)->nullable()->after('pair_id');
                $t->timestamp('pair_notice_at')->nullable()->after('final_mode'); // responsable prévenu : l'autre ne s'est pas inscrit
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stage_intern_invitations', 'pair_id')) {
            Schema::table('stage_intern_invitations', fn (Blueprint $t) => $t->dropColumn(['pair_id', 'final_mode', 'pair_notice_at']));
        }
        if (Schema::hasColumn('stage_teams', 'final_mode')) {
            Schema::table('stage_teams', fn (Blueprint $t) => $t->dropColumn('final_mode'));
        }
    }
};

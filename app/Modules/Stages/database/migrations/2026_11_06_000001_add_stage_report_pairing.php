<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rapports en binôme : un stagiaire peut déposer le rapport pour lui et son
 * binôme. Les deux rapports restent distincts (un par stagiaire) mais sont
 * liés : chaque dépôt, commentaire et décision vaut pour les deux.
 *  - stage_reports.pair_report_id            : rapport jumeau du binôme ;
 *  - stage_report_versions.submitted_by      : stagiaire qui a déposé la version ;
 *  - stage_report_comments.stage_request_id  : stagiaire auteur d'un message.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stage_reports', 'pair_report_id')) {
            Schema::table('stage_reports', function (Blueprint $t) {
                $t->unsignedBigInteger('pair_report_id')->nullable()->index();
            });
        }
        if (!Schema::hasColumn('stage_report_versions', 'submitted_by')) {
            Schema::table('stage_report_versions', function (Blueprint $t) {
                $t->unsignedBigInteger('submitted_by')->nullable();
            });
        }
        if (!Schema::hasColumn('stage_report_comments', 'stage_request_id')) {
            Schema::table('stage_report_comments', function (Blueprint $t) {
                $t->unsignedBigInteger('stage_request_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['stage_reports' => 'pair_report_id', 'stage_report_versions' => 'submitted_by', 'stage_report_comments' => 'stage_request_id'] as $table => $col) {
            if (Schema::hasColumn($table, $col)) {
                Schema::table($table, function (Blueprint $t) use ($table, $col) {
                    if ($table === 'stage_reports') {
                        $t->dropIndex(['pair_report_id']);
                    }
                    $t->dropColumn($col);
                });
            }
        }
    }
};

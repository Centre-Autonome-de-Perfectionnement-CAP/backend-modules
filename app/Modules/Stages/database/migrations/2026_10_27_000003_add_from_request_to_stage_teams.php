<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Binôme formé dès la demande de stage : les deux demandes sont décidées
 * ensemble (mêmes service et dates, une note de service chacun).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_teams', function (Blueprint $t) {
            $t->boolean('from_request')->default(false)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('stage_teams', function (Blueprint $t) {
            $t->dropColumn('from_request');
        });
    }
};

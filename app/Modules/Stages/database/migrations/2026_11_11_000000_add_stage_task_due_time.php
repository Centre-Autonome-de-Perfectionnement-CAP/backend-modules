<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Échéance d'une tâche : l'heure en plus du jour (HH:MM, heure du Bénin). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stage_tasks') && !Schema::hasColumn('stage_tasks', 'due_time')) {
            Schema::table('stage_tasks', function (Blueprint $t) {
                $t->string('due_time', 5)->nullable()->after('due_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stage_tasks', 'due_time')) {
            Schema::table('stage_tasks', fn (Blueprint $t) => $t->dropColumn('due_time'));
        }
    }
};

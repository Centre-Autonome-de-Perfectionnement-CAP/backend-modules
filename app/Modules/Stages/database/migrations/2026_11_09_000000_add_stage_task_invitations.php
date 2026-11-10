<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invitations sur une tâche :
 *  - stage_task_members.role : owner (stagiaire à qui la tâche est confiée,
 *    et son binôme), worker (invité pour y travailler), viewer (invité pour
 *    voir) ; counts : le travail compte pour lui (auteur) ou non ;
 *  - stage_task_staff : membres du personnel invités, pour voir ou avec
 *    plein pouvoir (revue, publication, invitations).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stage_task_members') && !Schema::hasColumn('stage_task_members', 'role')) {
            Schema::table('stage_task_members', function (Blueprint $t) {
                $t->string('role', 7)->default('owner');
                $t->boolean('counts')->default(true);
                $t->string('invited_by', 120)->nullable();
            });
        }
        if (!Schema::hasTable('stage_task_staff')) {
            Schema::create('stage_task_staff', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('task_id');
                $t->unsignedBigInteger('user_id')->index();
                $t->boolean('full_power')->default(false);
                $t->string('invited_by', 120)->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->unique(['task_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_task_staff');
        if (Schema::hasColumn('stage_task_members', 'role')) {
            Schema::table('stage_task_members', function (Blueprint $t) {
                $t->dropColumn(['role', 'counts', 'invited_by']);
            });
        }
    }
};

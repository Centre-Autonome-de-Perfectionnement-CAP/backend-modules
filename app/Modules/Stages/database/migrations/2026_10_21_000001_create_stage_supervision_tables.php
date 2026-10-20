<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Module Stages — Lot 2 : superviseurs (rôle + invitations), suivi des
 * stagiaires par superviseur, binômes / groupes de travail, groupe WhatsApp
 * annuel des stagiaires.
 *
 * Seul ajout hors tables du module : le rôle `superviseur` (inséré s'il
 * n'existe pas ; aucun rôle existant n'est modifié).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('roles')->where('slug', 'superviseur')->exists()) {
            DB::table('roles')->insert([
                'uuid' => (string) Str::uuid(), 'name' => 'Superviseur de stage', 'slug' => 'superviseur',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::create('stage_supervisor_invitations', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 30)->index();
            $t->string('name', 120)->nullable();
            $t->string('token_hash', 64)->unique();
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('expires_at');
            $t->timestamp('last_sent_at')->nullable();
            $t->unsignedSmallInteger('send_count')->default(0);
            $t->timestamp('accepted_at')->nullable();
            $t->foreignId('accepted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
        });

        Schema::create('stage_teams', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('stage_whatsapp_groups', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('year')->index();
            $t->string('jid', 60)->unique();
            $t->string('name', 100);
            $t->string('origin', 10);          // created | linked
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('dissolved_at')->nullable();
            $t->foreignId('dissolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::table('stage_requests', function (Blueprint $t) {
            $t->foreignId('team_id')->nullable()->after('horaires')->constrained('stage_teams')->nullOnDelete();
            $t->foreignId('wa_group_id')->nullable()->after('team_id')->constrained('stage_whatsapp_groups')->nullOnDelete();
            $t->timestamp('wa_added_at')->nullable()->after('wa_group_id');
            $t->timestamp('wa_invite_sent_at')->nullable()->after('wa_added_at');
            $t->timestamp('wa_removed_at')->nullable()->after('wa_invite_sent_at');
        });

        Schema::create('stage_supervisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['stage_request_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_supervisions');
        Schema::table('stage_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('wa_group_id');
            $t->dropConstrainedForeignId('team_id');
            $t->dropColumn(['wa_added_at', 'wa_invite_sent_at', 'wa_removed_at']);
        });
        Schema::dropIfExists('stage_whatsapp_groups');
        Schema::dropIfExists('stage_teams');
        Schema::dropIfExists('stage_supervisor_invitations');
        // Le rôle `superviseur` est conservé (des comptes peuvent y être rattachés).
    }
};

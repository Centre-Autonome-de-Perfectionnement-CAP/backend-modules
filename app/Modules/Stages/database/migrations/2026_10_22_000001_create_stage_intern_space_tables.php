<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Stages - Lot 3 : espace stagiaire (compte activé le jour du début,
 * empreinte WebAuthn), pointage géolocalisé, demandes du stagiaire
 * (permissions, prolongation, raccourcissement).
 *
 * Aucune donnée biométrique n'est stockée (clé publique de l'appareil
 * uniquement) ; pour les pointages, seule la distance au lieu de stage est
 * conservée, jamais les coordonnées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_requests', function (Blueprint $t) {
            $t->date('date_debut_reelle')->nullable()->after('date_fin');
            $t->string('activation_token_hash', 64)->nullable()->unique()->after('wa_removed_at');
            $t->timestamp('activation_sent_at')->nullable()->after('activation_token_hash');
            $t->unsignedTinyInteger('activation_reminders')->default(0)->after('activation_sent_at');
            $t->timestamp('activated_at')->nullable()->after('activation_reminders');
            $t->timestamp('withdrawn_at')->nullable()->after('activated_at');
        });

        Schema::create('stage_intern_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->unique()->constrained('stage_requests')->cascadeOnDelete();
            $t->string('password');
            $t->unsignedSmallInteger('reglement_version')->nullable();
            $t->timestamp('reglement_accepted_at')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
        });

        Schema::create('stage_intern_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained('stage_intern_accounts')->cascadeOnDelete();
            $t->string('token_hash', 64)->unique();
            $t->string('user_agent', 255)->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->index();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('stage_webauthn_credentials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained('stage_intern_accounts')->cascadeOnDelete();
            $t->string('credential_id', 512)->unique();
            $t->text('public_key');
            $t->unsignedBigInteger('sign_count')->default(0);
            $t->string('device_label', 120)->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('stage_attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->date('day');
            $t->string('kind', 12);                 // arrivee | pause_debut | pause_fin | sortie
            $t->timestamp('at');
            $t->unsignedSmallInteger('late_minutes')->default(0);
            $t->boolean('early')->default(false);
            $t->unsignedInteger('distance_m')->nullable();
            $t->unsignedSmallInteger('accuracy_m')->nullable();
            $t->boolean('position_checked')->default(false);
            $t->string('source', 14)->default('pointage'); // pointage | activation
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['stage_request_id', 'day', 'kind']);
        });

        // Tentatives refusées (diagnostic) : motif uniquement, aucune position.
        Schema::create('stage_attendance_failures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('reason', 40);
            $t->timestamp('created_at')->useCurrent();
            $t->index(['stage_request_id', 'created_at']);
        });

        Schema::create('stage_intern_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('kind', 16);                 // permission | prolongation | raccourcissement
            $t->string('type', 24)->nullable();     // pour les permissions
            $t->date('date_from')->nullable();
            $t->date('date_to')->nullable();
            $t->string('time_from', 5)->nullable();
            $t->string('time_to', 5)->nullable();
            $t->date('new_date_fin')->nullable();
            $t->text('motif');
            $t->string('file_path')->nullable();
            $t->string('status', 12)->default('pending')->index();
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_comment')->nullable();
            $t->timestamps();
            $t->index(['stage_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_intern_requests');
        Schema::dropIfExists('stage_attendance_failures');
        Schema::dropIfExists('stage_attendances');
        Schema::dropIfExists('stage_webauthn_credentials');
        Schema::dropIfExists('stage_intern_sessions');
        Schema::dropIfExists('stage_intern_accounts');
        Schema::table('stage_requests', function (Blueprint $t) {
            $t->dropUnique(['activation_token_hash']);
            $t->dropColumn(['date_debut_reelle', 'activation_token_hash', 'activation_sent_at', 'activation_reminders', 'activated_at', 'withdrawn_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stagiaires arrivés avant le système (ou en dehors) : le responsable de
 * division les invite par leur numéro WhatsApp ; ils remplissent eux-mêmes
 * leur dossier. Les documents portent les dates déclarées ; le système garde
 * la date réelle d'enregistrement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_intern_invitations', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 30)->index();
            $t->string('token_hash', 64)->unique();
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('expires_at');
            $t->timestamp('last_sent_at')->nullable();
            $t->unsignedSmallInteger('send_count')->default(1);
            $t->timestamp('used_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('stage_request_id')->nullable()->constrained('stage_requests')->nullOnDelete();
            $t->timestamps();
        });

        Schema::table('stage_requests', function (Blueprint $t) {
            // demande (formulaire du site) | invitation (stagiaire arrivé avant le système)
            $t->string('origin', 12)->default('demande')->after('status');
            // Présences et rapports comptés à partir de cette date (entrée dans le système)
            $t->date('attendance_from')->nullable()->after('date_debut_reelle');
        });

        Schema::table('stage_notes', function (Blueprint $t) {
            // Date réelle d'enregistrement (issued_at = date portée sur le document)
            $t->timestamp('recorded_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('stage_notes', fn (Blueprint $t) => $t->dropColumn('recorded_at'));
        Schema::table('stage_requests', fn (Blueprint $t) => $t->dropColumn(['origin', 'attendance_from']));
        Schema::dropIfExists('stage_intern_invitations');
    }
};

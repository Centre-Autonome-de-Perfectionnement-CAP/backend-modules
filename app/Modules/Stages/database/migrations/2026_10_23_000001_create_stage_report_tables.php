<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Stages — Lot 4 : rapports (périodiques + final).
 *
 * Chaque dépôt crée une version : rien n'est jamais écrasé. Commentaires et
 * décisions (superviseur, responsable) sont conservés dans l'historique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('kind', 10);                 // periodique | final
            $t->string('period_key', 20);           // J2026-11-03 | S2026-W45 | M2026-11 | FINAL
            $t->string('frequency', 12)->nullable();
            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->unsignedSmallInteger('current_version')->default(0);
            $t->string('status', 20)->default('submitted')->index();
            $t->boolean('late')->default(false);
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
            $t->unique(['stage_request_id', 'period_key']);
        });

        Schema::create('stage_report_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('stage_reports')->cascadeOnDelete();
            $t->unsignedSmallInteger('version');
            $t->string('file_path');
            $t->string('original_name', 200);
            $t->unsignedInteger('size');
            $t->text('note')->nullable();
            $t->boolean('late')->default(false);
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['report_id', 'version']);
        });

        Schema::create('stage_report_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('stage_reports')->cascadeOnDelete();
            $t->unsignedSmallInteger('version')->nullable();
            $t->string('author', 10);               // staff | stagiaire
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('body');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['report_id', 'id']);
        });

        Schema::create('stage_report_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('stage_reports')->cascadeOnDelete();
            $t->unsignedSmallInteger('version');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('role', 12);                 // superviseur | responsable
            $t->string('decision', 10);             // approved | rejected
            $t->text('comment')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['report_id', 'id']);
        });

        Schema::create('stage_report_notices', function (Blueprint $t) {
            // Rappels déjà envoyés (ouverture / veille de clôture), pour ne jamais les répéter.
            $t->id();
            $t->foreignId('stage_request_id')->constrained('stage_requests')->cascadeOnDelete();
            $t->string('period_key', 20);
            $t->string('kind', 12);                 // open | closing
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['stage_request_id', 'period_key', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_report_notices');
        Schema::dropIfExists('stage_report_reviews');
        Schema::dropIfExists('stage_report_comments');
        Schema::dropIfExists('stage_report_versions');
        Schema::dropIfExists('stage_reports');
    }
};

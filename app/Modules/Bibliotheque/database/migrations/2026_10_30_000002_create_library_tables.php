<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bibliothèque des travaux du CAP : rapports de fin de formation (stage),
 * mémoires de soutenance et travaux ajoutés par le responsable. Les
 * coordonnées des personnes restent privées (contact par messagerie relayée).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_works', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->string('share_token', 40)->unique();
            $t->string('kind', 20);                       // rapport_stage | memoire
            $t->string('source_type', 20);                // stage_report | defense | manuel
            $t->unsignedBigInteger('source_id')->nullable();
            $t->string('status', 12)->default('draft');   // proposed | draft | published | unpublished
            $t->string('visibility', 8)->default('public'); // public | link
            $t->boolean('downloadable')->default(true);
            $t->string('license', 20)->default('tous_droits');
            $t->string('title', 300);
            $t->text('summary')->nullable();
            $t->json('keywords')->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->string('etablissement', 150)->nullable();
            $t->string('universite', 150)->nullable();
            $t->string('filiere', 150)->nullable();
            $t->string('cycle', 12)->nullable();
            $t->unsignedTinyInteger('annee_etude')->nullable();
            $t->string('file_path')->nullable();
            $t->unsignedInteger('file_size')->nullable();
            $t->boolean('authors_consent')->default(false);
            $t->unsignedInteger('views')->default(0);
            $t->unsignedInteger('downloads')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['source_type', 'source_id']);
            $t->index(['status', 'visibility', 'year']);
        });

        Schema::create('library_people', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_id')->constrained('library_works')->cascadeOnDelete();
            $t->string('role', 16);                       // auteur | maitre | superviseur
            $t->string('name', 150);
            $t->string('fonction', 150)->nullable();
            $t->string('email', 254)->nullable();
            $t->string('phone', 30)->nullable();
            $t->unsignedTinyInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('library_contact_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_id')->constrained('library_works')->cascadeOnDelete();
            $t->foreignId('person_id')->nullable()->constrained('library_people')->nullOnDelete();
            $t->string('sender_name', 120);
            $t->string('sender_email', 254);
            $t->string('sender_phone', 30)->nullable();
            $t->text('message');
            $t->string('ip_hash', 64);
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_contact_messages');
        Schema::dropIfExists('library_people');
        Schema::dropIfExists('library_works');
    }
};

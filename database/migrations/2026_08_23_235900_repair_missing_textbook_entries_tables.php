<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX DE DÉRIVE (staging) : `textbook_entries` et `textbook_comments`
 * (dépendante via clé étrangère) sont marquées comme déjà migrées dans la
 * table `migrations`, mais n'existent plus physiquement en base —
 * probablement supprimées manuellement sans que le suivi Laravel soit mis
 * à jour en conséquence.
 *
 * Cette migration recrée les deux tables UNIQUEMENT si elles sont absentes
 * (Schema::hasTable). Sur un environnement où elles existent déjà
 * normalement (ex: prod si elle est saine), elle ne fait strictement rien.
 *
 * Schéma copié à l'identique depuis les migrations d'origine :
 *   - 2026_03_13_100000_create_textbook_entries_table.php
 *   - 2026_03_13_100001_create_textbook_comments_table.php
 *
 * Doit s'exécuter AVANT 2026_08_24_000001_add_emploi_du_temps_id_to_textbook_entries
 * (d'où le timestamp 2026_08_23, antérieur), sinon celle-ci échoue à nouveau.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('textbook_entries')) {
            Schema::create('textbook_entries', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                // Relations
                $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
                $table->foreignId('scheduled_course_id')->nullable()->constrained('scheduled_courses')->onDelete('set null');

                // Informations de la séance
                $table->date('session_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->decimal('hours_taught', 5, 2);

                // Contenu pédagogique
                $table->string('session_title');
                $table->text('content_covered');
                $table->text('objectives')->nullable();
                $table->text('teaching_methods')->nullable();

                // Travail à faire
                $table->text('homework')->nullable();
                $table->date('homework_due_date')->nullable();

                // Ressources et documents
                $table->json('resources')->nullable();
                $table->json('attachments')->nullable();

                // Présence et observations
                $table->integer('students_present')->nullable();
                $table->integer('students_absent')->nullable();
                $table->text('observations')->nullable();

                // Statut et validation
                $table->enum('status', ['draft', 'published', 'validated'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('validated_at')->nullable();

                $table->timestamps();

                $table->index('program_id');
                $table->index('scheduled_course_id');
                $table->index('session_date');
                $table->index('status');
                $table->index(['program_id', 'session_date']);
            });
        }

        if (!Schema::hasTable('textbook_comments')) {
            Schema::create('textbook_comments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->foreignId('textbook_entry_id')->constrained('textbook_entries')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

                $table->text('comment');
                $table->enum('type', ['comment', 'suggestion', 'correction'])->default('comment');

                $table->foreignId('parent_id')->nullable()->constrained('textbook_comments')->onDelete('cascade');

                $table->timestamps();

                $table->index('textbook_entry_id');
                $table->index('user_id');
                $table->index('parent_id');
            });
        }
    }

    public function down(): void
    {
        // Volontairement vide : c'est un correctif de dérive d'état, pas un
        // ajout de fonctionnalité. Un rollback accidentel ne doit pas
        // re-supprimer les tables.
    }
};

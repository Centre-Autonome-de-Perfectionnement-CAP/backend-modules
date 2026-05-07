<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            $table->json('resources')->nullable(); // URLs, fichiers, liens
            $table->json('attachments')->nullable(); // IDs de fichiers uploadés
            
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
            
            // Index pour optimisation
            $table->index('program_id');
            $table->index('scheduled_course_id');
            $table->index('session_date');
            $table->index('status');
            $table->index(['program_id', 'session_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('textbook_entries');
    }
};

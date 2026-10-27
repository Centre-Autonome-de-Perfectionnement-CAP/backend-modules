<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commentaires des acteurs sur les pièces d'un dossier de demande.
 *
 *  - document_request_file_comments : un commentaire par ligne, rattaché à
 *    une pièce identifiée par (file_source, file_key). Suppression douce :
 *    un commentaire supprimé reste consultable dans l'historique.
 *  - document_request_file_comment_revisions : trace immuable de chaque
 *    modification ou suppression (texte précédent, auteur, date).
 *
 * Idempotente et réversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_request_file_comments')) {
            Schema::create('document_request_file_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_request_id')->constrained('document_requests')->cascadeOnDelete();
                $table->string('file_source', 20);
                $table->string('file_key', 120);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('author_name', 200);
                $table->string('author_role', 60)->nullable();
                $table->text('body');
                $table->timestamp('edited_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['document_request_id', 'file_source', 'file_key'], 'drfc_request_file_idx');
            });
        }

        if (!Schema::hasTable('document_request_file_comment_revisions')) {
            Schema::create('document_request_file_comment_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('comment_id')->constrained('document_request_file_comments')->cascadeOnDelete();
                $table->string('action', 20); // edited | deleted
                $table->text('previous_body');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('author_name', 200);
                $table->timestamp('created_at')->nullable();

                $table->index('comment_id', 'drfcr_comment_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_file_comment_revisions');
        Schema::dropIfExists('document_request_file_comments');
    }
};

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
        Schema::create('textbook_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Relations
            $table->foreignId('textbook_entry_id')->constrained('textbook_entries')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Contenu du commentaire
            $table->text('comment');
            $table->enum('type', ['comment', 'suggestion', 'correction'])->default('comment');
            
            // Réponse à un autre commentaire (threading)
            $table->foreignId('parent_id')->nullable()->constrained('textbook_comments')->onDelete('cascade');
            
            $table->timestamps();
            
            // Index
            $table->index('textbook_entry_id');
            $table->index('user_id');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('textbook_comments');
    }
};

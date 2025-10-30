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
        Schema::table('files', function (Blueprint $table) {
            $table->text('description')->nullable()->after('extension');
            $table->boolean('is_official_document')->default(false)->after('description');
            $table->enum('document_categorie', ['administratif', 'pedagogique', 'legal', 'organisation'])->nullable()->after('is_official_document');
            $table->date('date_publication')->nullable()->after('document_categorie');
            
            // Ajouter des alias pour compatibilité avec le code existant
            $table->string('name')->nullable()->after('uuid')->virtualAs('stored_name');
            $table->string('path')->nullable()->after('file_path')->virtualAs('file_path');
            $table->string('disk')->nullable()->default('public')->after('path');
            
            $table->index('is_official_document');
            $table->index('document_categorie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'is_official_document',
                'document_categorie',
                'date_publication',
                'disk',
            ]);
            // Les colonnes virtuelles sont automatiquement supprimées
        });
    }
};

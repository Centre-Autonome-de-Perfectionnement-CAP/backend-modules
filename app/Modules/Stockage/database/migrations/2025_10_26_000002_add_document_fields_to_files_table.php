<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->text('description')->nullable()->after('original_name');
            $table->enum('document_categorie', ['administratif', 'pedagogique', 'legal', 'organisation'])->nullable()->after('description');
            $table->boolean('is_official_document')->default(false)->after('document_categorie')->index();
            $table->date('date_publication')->nullable()->after('is_official_document');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['description', 'document_categorie', 'is_official_document', 'date_publication']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('important_information_file')) {
            Schema::create('important_information_file', function (Blueprint $table) {
                $table->id();
                $table->foreignId('important_information_id')
                      ->constrained('important_informations')
                      ->onDelete('cascade');
                $table->foreignId('file_id')
                      ->constrained('files')
                      ->onDelete('cascade');
                $table->integer('order')->default(0);
                $table->timestamps();

                // Noms d'index courts pour éviter les erreurs MySQL
                $table->unique(['important_information_id', 'file_id'], 'ii_file_unique');
                $table->index('order', 'ii_file_order_idx');
            });
        } else {
            Schema::table('important_information_file', function (Blueprint $table) {
                if (!Schema::hasColumn('important_information_file', 'important_information_id')) {
                    $table->foreignId('important_information_id')
                          ->constrained('important_informations')
                          ->onDelete('cascade');
                }
                if (!Schema::hasColumn('important_information_file', 'file_id')) {
                    $table->foreignId('file_id')
                          ->constrained('files')
                          ->onDelete('cascade');
                }
                if (!Schema::hasColumn('important_information_file', 'order')) {
                    $table->integer('order')->default(0)->after('file_id');
                }

                $existingIndexes = \Illuminate\Support\Facades\DB::select(
                    "SELECT INDEX_NAME 
                     FROM INFORMATION_SCHEMA.STATISTICS 
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                    [\Illuminate\Support\Facades\DB::getDatabaseName(), 'important_information_file']
                );

                $existingIndexNames = array_map(fn($i) => $i->INDEX_NAME, $existingIndexes);

                if (!in_array('ii_file_unique', $existingIndexNames)) {
                    $table->unique(['important_information_id', 'file_id'], 'ii_file_unique');
                }

                if (!in_array('ii_file_order_idx', $existingIndexNames)) {
                    $table->index('order', 'ii_file_order_idx');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('important_information_file');
    }
};
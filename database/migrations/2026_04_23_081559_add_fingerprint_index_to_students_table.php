<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Gestion de la table 'students'
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'fingerprint_index')) {
                // On retire le ->after(...) pour éviter l'erreur de colonne inconnue
                $table->unsignedTinyInteger('fingerprint_index')
                      ->nullable()
                      ->unique();
            }
        });

        // 2. Gestion de la table 'attendances'
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'on_time')) {
                    $table->boolean('on_time')->default(true);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'fingerprint_index')) {
                $table->dropColumn('fingerprint_index');
            }
        });
        
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'on_time')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('on_time');
            });
        }
    }
};
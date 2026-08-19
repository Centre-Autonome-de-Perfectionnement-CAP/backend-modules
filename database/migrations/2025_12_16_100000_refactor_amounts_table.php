<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop indexes on level if SQLite or MySQL
        try {
            Schema::table('amounts', function (Blueprint $table) {
                $table->dropUnique('amounts_unique_combination');
                $table->dropIndex(['level']);
            });
        } catch (\Throwable $e) {
            // Index might not exist or already dropped
        }

        Schema::table('amounts', function (Blueprint $table) {
            $colsToDrop = [];
            foreach (['program_id', 'level', 'sponsored_amount'] as $col) {
                if (Schema::hasColumn('amounts', $col)) {
                    $colsToDrop[] = $col;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            $table->unsignedBigInteger('program_id')->nullable();
            $table->integer('level')->nullable();
            $table->decimal('sponsored_amount', 10, 2)->default(0);
        });
    }
};

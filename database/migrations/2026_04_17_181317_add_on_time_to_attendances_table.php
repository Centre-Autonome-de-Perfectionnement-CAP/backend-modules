<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // true = à l'heure | false = en retard (plus de 5 min après début du cours)
            if (!Schema::hasColumn('attendances', 'on_time')) {
                $table->boolean('on_time')->default(true)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('on_time');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            // program_id et sponsored_amount n'existent pas
            $table->dropColumn('level');
        });
    }

    public function down(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            $table->integer('level')->nullable();
        });
    }
};

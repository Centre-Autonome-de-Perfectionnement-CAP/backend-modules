<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn(['program_id', 'level', 'sponsored_amount']);
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

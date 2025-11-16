<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_elements', function (Blueprint $table) {
            $table->integer('credits')->default(1)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('course_elements', function (Blueprint $table) {
            $table->dropColumn('credits');
        });
    }
};

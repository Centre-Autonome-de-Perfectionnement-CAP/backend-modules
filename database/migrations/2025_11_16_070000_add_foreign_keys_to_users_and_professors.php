<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('rib')->references('id')->on('files')->onDelete('set null');
            $table->foreign('photo')->references('id')->on('files')->onDelete('set null');
            $table->foreign('ifu')->references('id')->on('files')->onDelete('set null');
        });

        Schema::table('professors', function (Blueprint $table) {
            $table->foreign('rib')->references('id')->on('files')->onDelete('set null');
            $table->foreign('ifu')->references('id')->on('files')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rib']);
            $table->dropForeign(['photo']);
            $table->dropForeign(['ifu']);
        });

        Schema::table('professors', function (Blueprint $table) {
            $table->dropForeign(['rib']);
            $table->dropForeign(['ifu']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('personal_information', 'entry_diploma_id')) {
            Schema::table('personal_information', function (Blueprint $table) {
                $table->foreignId('entry_diploma_id')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personal_information', 'entry_diploma_id')) {
            Schema::table('personal_information', function (Blueprint $table) {
                $table->dropColumn('entry_diploma_id');
            });
        }
    }
};

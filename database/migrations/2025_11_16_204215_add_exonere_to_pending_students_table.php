<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_students', function (Blueprint $table) {
            $table->enum('exonere', ['Oui', 'Non'])->default('Non')->after('sponsorise');
        });
    }

    public function down(): void
    {
        Schema::table('pending_students', function (Blueprint $table) {
            $table->dropColumn('exonere');
        });
    }
};

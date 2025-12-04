<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            $table->string('type')->default('scolarite')->after('id');
            $table->string('libelle')->nullable()->after('type');
            $table->boolean('is_active')->default(true)->after('sponsored_amount');
            $table->decimal('penalty_amount', 10, 2)->nullable()->after('is_active');
            $table->enum('penalty_type', ['fixed', 'percentage'])->default('fixed')->after('penalty_amount');
            $table->boolean('penalty_active')->default(false)->after('penalty_type');
        });
    }

    public function down(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            $table->dropColumn(['type', 'libelle', 'is_active', 'penalty_amount', 'penalty_type', 'penalty_active']);
        });
    }
};
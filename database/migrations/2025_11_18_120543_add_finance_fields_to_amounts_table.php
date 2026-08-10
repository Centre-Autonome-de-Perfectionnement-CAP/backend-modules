<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            if (!Schema::hasColumn('amounts', 'type')) {
                $table->string('type')
                    ->default('scolarite')
                    ->after('id');
            }

            if (!Schema::hasColumn('amounts', 'libelle')) {
                $table->string('libelle')
                    ->nullable()
                    ->after('type');
            }

            if (!Schema::hasColumn('amounts', 'is_active')) {
                $table->boolean('is_active')
                    ->default(true);
            }

            if (!Schema::hasColumn('amounts', 'penalty_amount')) {
                $table->decimal('penalty_amount', 10, 2)
                    ->nullable();
            }

            if (!Schema::hasColumn('amounts', 'penalty_type')) {
                $table->enum('penalty_type', ['fixed', 'percentage'])
                    ->default('fixed');
            }

            if (!Schema::hasColumn('amounts', 'penalty_active')) {
                $table->boolean('penalty_active')
                    ->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
            $columns = [
                'type',
                'libelle',
                'is_active',
                'penalty_amount',
                'penalty_type',
                'penalty_active',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('amounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
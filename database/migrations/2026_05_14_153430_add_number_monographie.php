<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contrat_programs', function (Blueprint $table) {
             
            $table->integer('number_monographie')->nullable();
            $table->decimal('amount_monographie', 10, 2)->nullable();
            $table->json('course_support_file')->nullable();
            $table->enum('updated_by',['professor','responsable_division'])->nullable();
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contrat_programs', function (Blueprint $table) {
            //
             $table->dropColumn([
                'number_monographie',
                'amount_monographie',
                'course_support_file',
                'updated_by'
            ]);
        });
    }
};

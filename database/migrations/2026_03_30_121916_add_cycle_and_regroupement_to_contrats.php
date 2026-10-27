<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->string('regroupement')->nullable();

            $table->foreignId('cycle_id')
                  ->nullable()
                  ->constrained('cycles')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('contrats', function (Blueprint $table) {
            // Supprimer la clé étrangère
            $table->dropForeign(['cycle_id']);

            // Supprimer les colonnes
            $table->dropColumn('cycle_id');
            $table->dropColumn('regroupement');
        });
    }
};
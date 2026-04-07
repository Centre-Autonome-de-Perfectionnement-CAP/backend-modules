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
     Schema::table('personal_information', function (Blueprint $table) {

            // colonne password nullable
            $table->string('password')->nullable();

            // clé étrangère vers roles
            $table->unsignedBigInteger('role_id')->nullable();

            // contrainte de clé étrangère
            $table->foreign('role_id')->references('id')->on('roles');
        });
}
      //

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('personal_information', function (Blueprint $table) {

            $table->dropForeign(['role_id']);
            $table->dropColumn(['password', 'role_id']);

        });
        //
    }
    };

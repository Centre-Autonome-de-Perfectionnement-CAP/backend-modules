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
            // Rendre les anciennes colonnes nullable si elles existent
            if (Schema::hasColumn('personal_information', 'birth_date')) {
                $table->date('birth_date')->nullable()->change();
            }
            
            if (Schema::hasColumn('personal_information', 'birth_place')) {
                $table->string('birth_place')->nullable()->change();
            }
            
            if (Schema::hasColumn('personal_information', 'birth_country')) {
                $table->string('birth_country')->nullable()->change();
            }
            
            // Ajouter/modifier les nouvelles colonnes
            if (!Schema::hasColumn('personal_information', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            } else {
                $table->date('date_of_birth')->nullable()->change();
            }
            
            if (!Schema::hasColumn('personal_information', 'place_of_birth')) {
                $table->string('place_of_birth')->nullable();
            } else {
                $table->string('place_of_birth')->nullable()->change();
            }
            
            if (!Schema::hasColumn('personal_information', 'country_of_birth')) {
                $table->string('country_of_birth')->nullable();
            } else {
                $table->string('country_of_birth')->nullable()->change();
            }
            
            if (!Schema::hasColumn('personal_information', 'entry_diploma_id')) {
                $table->unsignedBigInteger('entry_diploma_id')->nullable();
            } else {
                $table->unsignedBigInteger('entry_diploma_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne rien faire pour le rollback - on garde les colonnes nullable
        // Car on ne veut pas bloquer le système
    }
};

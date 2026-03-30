<?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up()
       {
           Schema::table('contacts', function (Blueprint $table) {
               // Vérifie que la colonne existe avant d'ajouter la contrainte
               if (Schema::hasColumn('contacts', 'academic_year_id')) {
                   $table->foreign('academic_year_id')
                         ->references('id')
                         ->on('academic_years')
                         ->onDelete('set null'); // ou 'cascade' selon le besoin
               }
           });
       }

       public function down()
       {
           Schema::table('contacts', function (Blueprint $table) {
               $table->dropForeign(['academic_year_id']);
           });
       }
   };

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected string $foreignKeyName = 'contacts_academic_year_fk';

    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'academic_year_id')) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('id');
            }

            // Vérifie que la FK n'existe pas encore avant de l'ajouter
            $exists = DB::table('information_schema.table_constraints')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'contacts')
                ->where('constraint_type', 'FOREIGN KEY')
                ->where('constraint_name', $this->foreignKeyName)
                ->exists();

            if (!$exists) {
                $table->foreign('academic_year_id', $this->foreignKeyName)
                      ->references('id')
                      ->on('academic_years')
                      ->onDelete('set null'); // ou 'cascade'
            }
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Vérifie que la FK existe avant de la supprimer
            $exists = DB::table('information_schema.table_constraints')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'contacts')
                ->where('constraint_type', 'FOREIGN KEY')
                ->where('constraint_name', $this->foreignKeyName)
                ->exists();

            if ($exists) {
                $table->dropForeign($this->foreignKeyName);
            }

            // Supprime la colonne si elle existe
            if (Schema::hasColumn('contacts', 'academic_year_id')) {
                $table->dropColumn('academic_year_id');
            }
        });
    }
};
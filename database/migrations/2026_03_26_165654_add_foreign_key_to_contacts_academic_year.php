<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected string $foreignKeyName = 'contacts_academic_year_fk';

    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Add the column if it doesn't exist
            if (!Schema::hasColumn('contacts', 'academic_year_id')) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('id');
            }

            // Only add FK if it doesn't exist
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
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Drop FK if exists
            $exists = DB::table('information_schema.table_constraints')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'contacts')
                ->where('constraint_type', 'FOREIGN KEY')
                ->where('constraint_name', $this->foreignKeyName)
                ->exists();

            if ($exists) {
                $table->dropForeign($this->foreignKeyName);
            }

            // Drop the column if exists
            if (Schema::hasColumn('contacts', 'academic_year_id')) {
                $table->dropColumn('academic_year_id');
            }
        });
    }
};
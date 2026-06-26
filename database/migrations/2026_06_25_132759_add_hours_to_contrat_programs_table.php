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
    Schema::table('contrat_programs', function (Blueprint $table) {
        $table->decimal('hours', 8, 2)->nullable()->after('amount_program');
    });
}

public function down()
{
    Schema::table('contrat_programs', function (Blueprint $table) {
        $table->dropColumn('hours');
    });
}
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
<<<<<<< HEAD
            // program_id et sponsored_amount n'existent pas
            // $table->dropColumn('level');
=======
            $table->dropForeign(['program_id']);
            $table->dropColumn(['program_id', 'level', 'sponsored_amount']);
>>>>>>> 7854261 (commit)
        });
    }

    public function down(): void
    {
        Schema::table('amounts', function (Blueprint $table) {
<<<<<<< HEAD
            $table->integer('level')->nullable();
=======
            $table->unsignedBigInteger('program_id')->nullable();
            $table->integer('level')->nullable();
            $table->decimal('sponsored_amount', 10, 2)->default(0);
>>>>>>> 7854261 (commit)
        });
    }
};

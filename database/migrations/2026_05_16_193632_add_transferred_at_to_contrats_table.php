 
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
       Schema::table('contrats', function (Blueprint $table) {
            $table->timestamp('transfered_at')->nullable()->after('status');
            $table->timestamp('link_expires_at')->nullable()->after('transfered_at');
        });
    }

    public function down()
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn(['transfered_at', 'link_expires_at']);
        });
    }
};

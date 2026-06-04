<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('slug', 'responsable-division')->update(['name' => 'Responsable Division']);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'responsable-division')->update(['name' => 'Chef Division']);
    }
};

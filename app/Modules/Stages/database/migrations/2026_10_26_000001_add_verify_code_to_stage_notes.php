<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cachet numérique des notes de service : code de vérification unique,
 * imprimé sur la note (et dans son QR code), vérifiable sur le site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_notes', function (Blueprint $t) {
            $t->string('verify_code', 9)->nullable()->unique()->after('number');
        });

        // Notes déjà émises : un code chacune.
        foreach (DB::table('stage_notes')->whereNull('verify_code')->pluck('id') as $id) {
            do {
                $code = App\Modules\Stages\Services\StageNoteCode::generate();
            } while (DB::table('stage_notes')->where('verify_code', $code)->exists());
            DB::table('stage_notes')->where('id', $id)->update(['verify_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('stage_notes', function (Blueprint $t) {
            $t->dropUnique(['verify_code']);
            $t->dropColumn('verify_code');
        });
    }
};

<?php

use App\Modules\Stages\Support\StudyLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Filière (libre) et niveau d'études (cycle + année, listes) séparés.
 * Les dossiers existants sont convertis au mieux ; ce qui n'est pas reconnu
 * passe en « autre » sans rien perdre (le libellé d'origine reste la filière).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stage_requests', function (Blueprint $t) {
            $t->string('filiere', 150)->nullable()->after('filiere_niveau');
            $t->string('cycle', 12)->nullable()->after('filiere');
            $t->unsignedTinyInteger('annee_etude')->nullable()->after('cycle');
        });

        DB::table('stage_requests')->whereNull('cycle')->orderBy('id')->select('id', 'filiere_niveau')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('stage_requests')->where('id', $r->id)->update(StudyLevel::guess((string) $r->filiere_niveau));
                }
            });
    }

    public function down(): void
    {
        Schema::table('stage_requests', fn (Blueprint $t) => $t->dropColumn(['filiere', 'cycle', 'annee_etude']));
    }
};

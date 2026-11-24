<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Stages : le NPI (Numéro Personnel d'Identification) du demandeur,
 * pour reconnaître une même personne d'un stage à l'autre. Facultatif en base :
 * les stagiaires déjà inscrits le renseignent à leur prochaine connexion.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stage_requests') && !Schema::hasColumn('stage_requests', 'npi')) {
            Schema::table('stage_requests', function (Blueprint $t) {
                $t->string('npi', 10)->nullable()->index()->after('prenoms');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stage_requests', 'npi')) {
            Schema::table('stage_requests', fn (Blueprint $t) => $t->dropColumn('npi'));
        }
    }
};

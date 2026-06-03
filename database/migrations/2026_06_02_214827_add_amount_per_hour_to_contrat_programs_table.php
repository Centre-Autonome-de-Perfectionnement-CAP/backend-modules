 <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la colonne `amount_per_hour` à la table pivot `contrat_programs`.
 *
 * Exécuter avec : php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrat_programs', function (Blueprint $table) {
           $table->decimal('amount_per_hour', 12, 2)->nullable()
      ->comment('Montant par heure défini pour ce programme dans ce contrat');
        });
    }

    public function down(): void
    {
        Schema::table('contrat_programs', function (Blueprint $table) {
            $table->dropColumn('amount_per_hour');
        });
    }
};

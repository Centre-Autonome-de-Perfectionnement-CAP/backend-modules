<?php

namespace App\Modules\Demandes\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * CORRECTION CRITIQUE — Bug "Aucune demande" côté secrétaire
 *
 * Le dossier s'appelait "Routes" (majuscule) dans proj2.
 * Sur Linux (case-sensitive), le chemin __DIR__ . '/../Routes/api.php'
 * pointait dans le vide → les routes n'étaient jamais enregistrées.
 *
 * ACTION REQUISE :
 *   1. Remplacer ce fichier dans app/Modules/Demandes/Providers/
 *   2. Renommer app/Modules/Demandes/Routes/ → app/Modules/Demandes/routes/
 *      (sur Linux : mv Routes routes)
 */
class DemandesServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}

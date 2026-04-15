<?php

namespace App\Modules\Demandes\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\Services\NotificationService;
use App\Modules\Demandes\Services\TransitionService;

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

    

    public function register(): void
    {
        // Singletons explicites — lisibilité + perf (pas de re-instanciation)
        $this->app->singleton(DocumentRequestQueryService::class);
        $this->app->singleton(DocumentRequestHistoryService::class);
        $this->app->singleton(NotificationService::class);

        $this->app->singleton(TransitionService::class, function ($app) {
            return new TransitionService(
                $app->make(DocumentRequestHistoryService::class),
                $app->make(NotificationService::class),
            );
        });
    }


    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');



        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}

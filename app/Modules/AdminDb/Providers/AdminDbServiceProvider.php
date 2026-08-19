<?php

namespace App\Modules\AdminDb\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider isolé pour l'outil d'administration brute des tables
 * (document_requests, users). Suit le même schéma que les autres modules
 * du projet (AttestationServiceProvider, DemandesServiceProvider) :
 * ne fait que charger les routes, aucun binding requis.
 *
 * ENREGISTREMENT REQUIS (une seule ligne, à faire manuellement) :
 *   - Laravel ≤10 : ajouter AdminDbServiceProvider::class dans le tableau
 *     'providers' de config/app.php.
 *   - Laravel ≥11 : ajouter AdminDbServiceProvider::class dans le tableau
 *     retourné par bootstrap/providers.php.
 *
 * SUPPRESSION : retirer cette ligne, puis supprimer le dossier
 * App/Modules/AdminDb au complet.
 */
class AdminDbServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['router']
            ->prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../routes/api.php');
    }
}

<?php

namespace App\Modules\EmploiDuTemps\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class EmploiDuTempsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Charger les routes avec le middleware API
        Route::middleware('api')
            ->group(__DIR__.'/../routes/api.php');
            
        // Charger les migrations depuis le dossier database/migrations global
        // (Les migrations ont été placées dans database/migrations au lieu de app/Modules/EmploiDuTemps/database/migrations)
    }
}

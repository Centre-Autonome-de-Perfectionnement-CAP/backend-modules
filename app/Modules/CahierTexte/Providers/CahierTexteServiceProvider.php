<?php

namespace App\Modules\CahierTexte\Providers;

use Illuminate\Support\ServiceProvider;

class CahierTexteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Charger les routes API
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}

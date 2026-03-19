<?php

namespace App\Modules\RH\Providers;

use Illuminate\Support\ServiceProvider;

class RHServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Charger les routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}

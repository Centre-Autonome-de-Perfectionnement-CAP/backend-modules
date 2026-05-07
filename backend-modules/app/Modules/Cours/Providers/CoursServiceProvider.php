<?php

namespace App\Modules\Cours\Providers;

use Illuminate\Support\ServiceProvider;

class CoursServiceProvider extends ServiceProvider
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
        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Charger les routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}

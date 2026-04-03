<?php

namespace App\Modules\Notes\Providers;

use Illuminate\Support\ServiceProvider;

class NotesServiceProvider extends ServiceProvider
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
        // Charger les routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        
        // Charger les migrations si nécessaire
        // $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}

<?php

namespace App\Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auth');
        
        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}

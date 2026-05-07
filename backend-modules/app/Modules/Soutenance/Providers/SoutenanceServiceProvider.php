<?php

namespace App\Modules\Soutenance\Providers;

use Illuminate\Support\ServiceProvider;

class SoutenanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

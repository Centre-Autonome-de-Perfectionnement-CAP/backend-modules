<?php

namespace App\Modules\Alumni\Providers;

use Illuminate\Support\ServiceProvider;

class AlumniServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}

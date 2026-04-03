<?php

namespace App\Modules\Attendance\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        //  Charge les vues du module avec le namespace "attendance"
         $this->loadViewsFrom(__DIR__.'/../Resources/views', 'attendance');

    }
}

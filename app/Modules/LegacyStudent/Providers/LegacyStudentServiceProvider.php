<?php

namespace App\Modules\LegacyStudent\Providers;

use Illuminate\Support\ServiceProvider;

class LegacyStudentServiceProvider extends ServiceProvider
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

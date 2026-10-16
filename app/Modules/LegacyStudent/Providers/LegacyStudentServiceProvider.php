<?php

namespace App\Modules\LegacyStudent\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LegacyStudentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // FUSION develop/main : sur main, ces routes étaient (re)chargées avec le
        // middleware 'api' par bootstrap/app.php (then:). Convention develop :
        // le provider les charge lui-même avec ce middleware. Le fichier porte
        // déjà le préfixe 'api'.
        Route::middleware('api')->group(__DIR__ . '/../routes/api.php');
    }
}

<?php

namespace App\Modules\Bibliotheque\Providers;

use App\Modules\Stages\Support\StageAccess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/** Bibliothèque des travaux : rapports de fin de formation et mémoires. */
class BibliothequeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'bibliotheque');

        Gate::define('bibliotheque.manage', fn ($user) => StageAccess::isLead($user));

        // Messages aux auteurs : quelques-uns par visiteur, contre les abus.
        RateLimiter::for('bibliotheque-contact', fn (Request $r) => [
            Limit::perMinutes(10, 3)->by('ip:' . $r->ip()),
            Limit::perDay(30)->by('travail:' . $r->route('slug')),
        ]);
    }
}

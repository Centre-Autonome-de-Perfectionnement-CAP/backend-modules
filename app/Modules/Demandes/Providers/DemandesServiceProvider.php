<?php

namespace App\Modules\Demandes\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\Services\NotificationService;
use App\Modules\Demandes\Services\TransitionService;

class DemandesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singletons explicites — lisibilité + perf (pas de re-instanciation)
        $this->app->singleton(DocumentRequestQueryService::class);
        $this->app->singleton(DocumentRequestHistoryService::class);
        $this->app->singleton(NotificationService::class);

        $this->app->singleton(TransitionService::class, function ($app) {
            return new TransitionService(
                $app->make(DocumentRequestHistoryService::class),
                $app->make(NotificationService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}

<?php

namespace App\Modules\Demandes\Providers;

use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\Services\DocumentRequestNotificationService;
use Illuminate\Support\ServiceProvider;

class DemandesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentRequestHistoryService::class);
        $this->app->singleton(DocumentRequestNotificationService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}

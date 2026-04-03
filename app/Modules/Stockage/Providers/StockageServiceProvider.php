<?php

namespace App\Modules\Stockage\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Stockage\Models\File;
use App\Modules\Stockage\Policies\FilePolicy;
use App\Modules\Stockage\Services\FileStorageService;
use App\Modules\Stockage\Services\PermissionService;
use App\Modules\Stockage\Services\FileShareService;

class StockageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer les services en tant que singletons
        $this->app->singleton(FileStorageService::class, function ($app) {
            return new FileStorageService();
        });

        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService();
        });

        $this->app->singleton(FileShareService::class, function ($app) {
            return new FileShareService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Charger les routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        
        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Enregistrer les policies
        $this->registerPolicies();
        
        // Publier les configurations (optionnel)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/stockage.php' => config_path('stockage.php'),
            ], 'stockage-config');
        }
    }

    /**
     * Enregistre les policies du module.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(File::class, FilePolicy::class);
    }
}

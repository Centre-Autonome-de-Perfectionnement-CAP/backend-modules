<?php

namespace App\Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Core\Services\MailService;
use App\Modules\Core\Services\PdfService;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer les services en tant que singleton
        $this->app->singleton(MailService::class, function ($app) {
            return new MailService();
        });

        $this->app->singleton(PdfService::class, function ($app) {
            return new PdfService();
        });

        // Créer des alias pour faciliter l'utilisation
        $this->app->alias(MailService::class, 'core.mail');
        $this->app->alias(PdfService::class, 'core.pdf');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Charger les vues (templates)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'core');

        // Publier les vues pour permettre la personnalisation
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/core'),
        ], 'core-views');
    }
}

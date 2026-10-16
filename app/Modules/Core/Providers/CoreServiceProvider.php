<?php

namespace App\Modules\Core\Providers;

use Illuminate\Support\Facades\Route;
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
        // FUSION develop/main : routes du module Core (OTP, ajoutées sur main).
        // Sur main elles étaient chargées par bootstrap/app.php (then:) ; selon la
        // convention develop, chaque module charge ses routes dans son provider.
        // Le fichier porte déjà le préfixe 'api/core' → middleware 'api' seul.
        if (file_exists(__DIR__.'/../routes/api.php')) {
            Route::middleware('api')->group(__DIR__.'/../routes/api.php');
        }

        // Charger les vues (templates)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'core');

        // Publier les vues pour permettre la personnalisation
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/core'),
        ], 'core-views');
    }
}

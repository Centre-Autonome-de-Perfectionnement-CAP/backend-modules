<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Charger les routes API des modules
            $modules = ['Auth', 'Stockage', 'Inscription', 'Finance', 'Cours', 'RH'];
            foreach ($modules as $module) {
                $routePath = base_path("app/Modules/{$module}/routes/api.php");
                if (file_exists($routePath)) {
                    Route::prefix('api')
                        ->middleware('api')
                        ->group($routePath);
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ajouter CORS en premier
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Désactiver l'authentification par défaut sur les routes API
        $middleware->api(remove: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

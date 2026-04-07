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
            $modules = ['Auth', 'Stockage', 'Inscription', 'Finance', 'Cours', 'RH', 'Contact', 'Notes', 'EmploiDuTemps'];
            foreach ($modules as $module) {
                $routePath = base_path("app/Modules/{$module}/routes/api.php");
                if (file_exists($routePath)) {
                    Route::middleware('api')
                        ->group($routePath);
                }
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->api(remove: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        
        // Configure auth middleware to return JSON for API
        $middleware->redirectGuestsTo(fn () => abort(401, 'Authentification requise'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\BusinessException $e, $request) {
            return $e->render();
        });
        
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ressource introuvable',
                    'error_code' => 'RESOURCE_NOT_FOUND',
                ], 404);
            }
        });
        
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les données fournies sont invalides',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
        
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise',
                    'error_code' => 'AUTHENTICATION_REQUIRED',
                ], 401);
            }
        });
        
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé',
                    'error_code' => 'UNAUTHORIZED',
                ], 403);
            }
        });
        
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::error('Erreur base de données', [
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql() ?? null,
                ]);
                
                $message = 'Erreur lors de l\'opération sur la base de données';
                
                // Messages personnalisés selon le type d'erreur
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $message = 'Cette entrée existe déjà';
                } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
                    $message = 'Impossible de supprimer cette ressource car elle est utilisée ailleurs';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'DATABASE_ERROR',
                ], 500);
            }
        });
        
        // Gestion générique des exceptions
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson()) {
                \Illuminate\Support\Facades\Log::error('Erreur non gérée', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'Une erreur interne est survenue';
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error_code' => 'INTERNAL_ERROR',
                ], 500);
            }
        });
    })->create();

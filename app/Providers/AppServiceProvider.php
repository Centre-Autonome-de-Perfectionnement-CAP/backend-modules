<?php

namespace App\Providers;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;        // ← AJOUTER

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void {

        Schema::defaultStringLength(191);      // ← AJOUTER

        Scramble::configure()
        ->routes(function (Route $route) {
            return Str::startsWith($route->uri, 'api/');
        })->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });
    }
}
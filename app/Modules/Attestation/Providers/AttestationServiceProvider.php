<?php

namespace App\Modules\Attestation\Providers;

use Illuminate\Support\ServiceProvider;

class AttestationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app['router']
            ->middleware('api')          // ← applique le groupe API (CORS inclus)
            ->group(__DIR__ . '/../routes/api.php');
    }
}

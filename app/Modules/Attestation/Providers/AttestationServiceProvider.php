<?php

namespace App\Modules\Attestation\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Attestation\Services\{EligibilityService, AttestationStatusService, DemandeSubmissionService};

/**
 * CORRECTIF (v2) — basé sur le AttestationServiceProvider réel.
 *
 * L'original ne fait QUE charger les routes via
 * $this->app['router']->middleware('api')->group(...) — aucun binding
 * de service (tout était résolu automatiquement par l'auto-wiring Laravel,
 * ce qui fonctionne très bien pour des classes sans interface).
 *
 * AJOUTÉ (B1.1) : bindings explicites pour les nouveaux services extraits
 * (EligibilityService, AttestationStatusService, DemandeSubmissionService).
 * Le `bind()` simple suffit (pas de singleton nécessaire, ces services
 * sont sans état et bon marché à instancier) — cohérent avec le choix de
 * ne pas binder AttestationService lui-même dans l'original (auto-wiring).
 */
class AttestationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // AJOUT (B1.1) — services extraits du contrôleur, sans état.
        // Pas strictement nécessaire (auto-wiring Laravel les résoudrait
        // de toute façon), mais explicite et documenté.
        $this->app->bind(EligibilityService::class);
        $this->app->bind(AttestationStatusService::class);
        $this->app->bind(DemandeSubmissionService::class);
    }

    public function boot(): void
    {
        $this->app['router']
            ->middleware('api')          // ← applique le groupe API (CORS inclus)
            ->group(__DIR__ . '/../routes/api.php');
    }
}

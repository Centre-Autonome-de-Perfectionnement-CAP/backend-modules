<?php

namespace App\Modules\Attestation\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Modules\Attestation\Services\{EligibilityService, AttestationStatusService, DemandeSubmissionService};

/**
 * CORRECTIF (v2) - basé sur le AttestationServiceProvider réel.
 *
 * L'original ne fait QUE charger les routes via
 * $this->app['router']->middleware('api')->group(...) - aucun binding
 * de service (tout était résolu automatiquement par l'auto-wiring Laravel,
 * ce qui fonctionne très bien pour des classes sans interface).
 *
 * AJOUTÉ (B1.1) : bindings explicites pour les nouveaux services extraits
 * (EligibilityService, AttestationStatusService, DemandeSubmissionService).
 * Le `bind()` simple suffit (pas de singleton nécessaire, ces services
 * sont sans état et bon marché à instancier) - cohérent avec le choix de
 * ne pas binder AttestationService lui-même dans l'original (auto-wiring).
 */
class AttestationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // AJOUT (B1.1) - services extraits du contrôleur, sans état.
        // Pas strictement nécessaire (auto-wiring Laravel les résoudrait
        // de toute façon), mais explicite et documenté.
        $this->app->bind(EligibilityService::class);
        $this->app->bind(AttestationStatusService::class);
        $this->app->bind(DemandeSubmissionService::class);
    }

    public function boot(): void
    {
        // ── AJOUT (audit sécurité 13/10/2026) : limitation de débit des routes
        // publiques du site vitrine.
        //   - lecture  : 300 / min / IP - rend l'énumération des références
        //     et matricules impraticable, sans gêner un usage normal même si
        //     plusieurs étudiants partagent une IP (cybercafé, proxy, NAT) ;
        //   - écriture : 20 / min / (IP + étudiant ciblé) - anti-spam de
        //     dossiers/notifications ; la clé inclut le matricule/la référence
        //     pour ne jamais pénaliser des étudiants différents sur une même IP.
        RateLimiter::for('attestation-public-read', fn (Request $request) =>
            Limit::perMinute(300)->by('att-read|' . $request->ip())
        );

        RateLimiter::for('attestation-public-write', fn (Request $request) =>
            Limit::perMinute(20)->by('att-write|' . $request->ip() . '|' . strtoupper(trim((string) (
                $request->input('matricule') ?? $request->input('reference') ?? $request->input('link_id') ?? ''
            ))))
        );

        $this->app['router']
            ->middleware('api')          // ← applique le groupe API (CORS inclus)
            ->group(__DIR__ . '/../routes/api.php');
    }
}

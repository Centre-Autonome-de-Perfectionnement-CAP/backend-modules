<?php

namespace App\Modules\Demandes\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Demandes\Models\DocumentRequest;
use App\Modules\Demandes\Policies\DocumentRequestPolicy;
use App\Modules\Demandes\Services\DocumentRequestQueryService;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\Services\NotificationService;
use App\Modules\Demandes\Services\TransitionService;
use App\Modules\Demandes\Services\WhatsAppService;
use App\Modules\Demandes\Services\SecretaryFileService;

/**
 * CORRECTIF (v2) — basé sur le DemandesServiceProvider réel.
 *
 * Conservé à l'identique : tous les singletons existants (WhatsAppService,
 * NotificationService, DocumentRequestQueryService, DocumentRequestHistoryService,
 * TransitionService), loadRoutesFrom() et loadMigrationsFrom().
 *
 * Ajouté (B1.1 / B1.3) :
 *   - SecretaryFileService en singleton (nouveau service extrait du contrôleur)
 *   - Gate::policy() pour DocumentRequestPolicy, absente de l'original
 *     (la vérification de rôle était inline dans le contrôleur)
 */
class DemandesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── WhatsApp (singleton partagé — évite de recréer le client Twilio) ──
        $this->app->singleton(WhatsAppService::class);

        // ── NotificationService : dépend de WhatsAppService ───────────────────
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(WhatsAppService::class),
            );
        });

        // ── Autres singletons (inchangés) ─────────────────────────────────────
        $this->app->singleton(DocumentRequestQueryService::class);
        $this->app->singleton(DocumentRequestHistoryService::class);

        $this->app->singleton(TransitionService::class, function ($app) {
            return new TransitionService(
                $app->make(DocumentRequestHistoryService::class),
                $app->make(NotificationService::class),
            );
        });

        // ── AJOUT (B1.1) : service extrait de DocumentRequestController ───────
        $this->app->singleton(SecretaryFileService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // ── AJOUT (B1.3) : Policy absente de l'original ───────────────────────
        Gate::policy(DocumentRequest::class, DocumentRequestPolicy::class);
    }
}

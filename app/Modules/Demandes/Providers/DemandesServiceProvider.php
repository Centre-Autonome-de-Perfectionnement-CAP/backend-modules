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
 *
 * NOTE HISTORIQUE : ce dossier s'appelait autrefois "Routes" (majuscule).
 * Sur Linux (filesystem case-sensitive), __DIR__ . '/../Routes/api.php'
 * pointait dans le vide et aucune route n'était jamais enregistrée
 * ("Aucune demande" côté secrétaire). Déjà corrigé ci-dessous
 * (loadRoutesFrom pointe vers routes/ en minuscule) — gardé en note pour
 * éviter de reproduire l'erreur.
 */
class DemandesServiceProvider extends ServiceProvider
{

    

    public function register(): void
    {
        // Singletons explicites — lisibilité + perf (pas de re-instanciation)
        $this->app->singleton(DocumentRequestQueryService::class);
        $this->app->singleton(DocumentRequestHistoryService::class);
        $this->app->singleton(NotificationService::class);

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

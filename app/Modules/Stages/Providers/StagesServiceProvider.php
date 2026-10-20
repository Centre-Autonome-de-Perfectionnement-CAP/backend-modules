<?php

namespace App\Modules\Stages\Providers;

use App\Modules\Stages\Console\Commands\ProcessStageDeadlines;
use App\Modules\Stages\Console\Commands\SyncStageWhatsAppGroup;
use App\Modules\Stages\Http\Middleware\RestrictSupervisorScope;
use App\Modules\Stages\Services\StageWhatsAppGroupService;
use App\Modules\Stages\Services\SupervisionService;
use App\Modules\Stages\Services\SupervisorInvitationService;
use App\Modules\Stages\Services\StageNotifier;
use App\Modules\Stages\Services\StageRequestService;
use App\Modules\Stages\Services\StageSettingsService;
use App\Modules\Stages\Services\StageSuiviOtpService;
use App\Modules\Stages\Support\StageAccess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class StagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StageSettingsService::class);
        $this->app->singleton(StageNotifier::class);
        $this->app->singleton(StageSuiviOtpService::class);
        $this->app->singleton(StageRequestService::class);
        $this->app->singleton(SupervisionService::class);
        $this->app->singleton(SupervisorInvitationService::class);
        $this->app->singleton(StageWhatsAppGroupService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'stages');

        Gate::define('stages.manage', fn ($user) => StageAccess::canManage($user));
        Gate::define('stages.lead', fn ($user) => StageAccess::isLead($user));

        // Les comptes superviseurs n'ont accès qu'au module Stages : filtre
        // global (certains modules enregistrent leurs routes hors du groupe
        // « api »).
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)->pushMiddleware(RestrictSupervisorScope::class);

        RateLimiter::for('stages-public-read', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));
        RateLimiter::for('stages-public-submit', fn (Request $r) => [
            Limit::perMinute(3)->by($r->ip()),
            Limit::perHour(10)->by($r->ip()),
        ]);
        RateLimiter::for('stages-public-code', fn (Request $r) => [
            Limit::perMinute(10)->by($r->ip()),
            Limit::perMinute(6)->by('ref:' . strtoupper((string) $r->input('reference'))),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([ProcessStageDeadlines::class, SyncStageWhatsAppGroup::class]);
        }
    }
}

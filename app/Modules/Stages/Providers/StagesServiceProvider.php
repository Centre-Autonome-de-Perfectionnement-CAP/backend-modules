<?php

namespace App\Modules\Stages\Providers;

use App\Modules\Stages\Console\Commands\ProcessStageDeadlines;
use App\Modules\Stages\Console\Commands\SyncStageWhatsAppGroup;
use App\Modules\Stages\Console\Commands\ProcessStageActivations;
use App\Modules\Stages\Console\Commands\ProcessStageReports;
use App\Modules\Stages\Services\ReportService;
use App\Modules\Stages\Services\AttestationService;
use App\Modules\Stages\Console\Commands\ProcessStageAttestations;
use App\Modules\Stages\Services\AttendanceService;
use App\Modules\Stages\Services\InternActivationService;
use App\Modules\Stages\Services\InternAuthService;
use App\Modules\Stages\Services\InternRequestService;
use App\Modules\Stages\Services\InternWebAuthnService;
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
        // Bibliothèque WebAuthn vendorisée (voir Vendor/WebAuthn/VENDOR-NOTES.md).
        spl_autoload_register(static function (string $class): void {
            $prefix = 'lbuchs\\WebAuthn\\';
            if (str_starts_with($class, $prefix)) {
                $file = __DIR__ . '/../Vendor/WebAuthn/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        });

        $this->app->singleton(StageSettingsService::class);
        $this->app->singleton(StageNotifier::class);
        $this->app->singleton(StageSuiviOtpService::class);
        $this->app->singleton(StageRequestService::class);
        $this->app->singleton(SupervisionService::class);
        $this->app->singleton(SupervisorInvitationService::class);
        $this->app->singleton(StageWhatsAppGroupService::class);
        $this->app->singleton(AttendanceService::class);
        $this->app->singleton(InternAuthService::class);
        $this->app->singleton(InternWebAuthnService::class);
        $this->app->singleton(InternActivationService::class);
        $this->app->singleton(InternRequestService::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(AttestationService::class);
        $this->app->singleton(\App\Modules\Stages\Services\DashboardService::class);
        $this->app->singleton(\App\Modules\Stages\Services\ExportService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'stages');

        Gate::define('stages.manage', fn ($user) => StageAccess::canManage($user));
        Gate::define('stages.lead', fn ($user) => StageAccess::isLead($user));
        Gate::define('stages.attestations', fn ($user) => StageAccess::canHandleAttestations($user));

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
        // Activation, connexion, mot de passe oublié : limite par lien ou par
        // identifiant (plusieurs essais par personne), et une limite large par
        // adresse : le premier jour, tous les stagiaires partagent le Wi-Fi du CAP.
        RateLimiter::for('stages-intern-auth', fn (Request $r) => [
            Limit::perMinute(60)->by('ip:' . $r->ip()),
            Limit::perMinute(10)->by('id:' . hash('sha256', mb_strtolower(trim((string) ($r->input('token') ?: $r->input('identifiant') ?: $r->ip()))))),
        ]);
        // Espace stagiaire : limites par stagiaire connecté, pas par adresse IP
        // (sur le Wi-Fi du CAP, tous les stagiaires partagent la même adresse).
        $intern = fn (Request $r) => 'stagiaire:' . ($r->attributes->get('intern')?->id ?? $r->ip());
        RateLimiter::for('stages-intern', fn (Request $r) => Limit::perMinute(120)->by($intern($r)));
        RateLimiter::for('stages-intern-medium', fn (Request $r) => Limit::perMinute(20)->by($intern($r)));
        RateLimiter::for('stages-intern-write', fn (Request $r) => Limit::perMinute(10)->by($intern($r)));

        if ($this->app->runningInConsole()) {
            $this->commands([ProcessStageDeadlines::class, SyncStageWhatsAppGroup::class, ProcessStageActivations::class, ProcessStageReports::class, ProcessStageAttestations::class]);
        }
    }
}

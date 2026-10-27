<?php

namespace App\Modules\WhatsApp\Providers;

use App\Modules\WhatsApp\Console\Commands\StartWhatsAppNode;
use App\Modules\WhatsApp\Services\WhatsAppBridgeClient;
use App\Modules\WhatsApp\Services\WhatsAppProcessManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppBridgeClient::class);
        // Hôte et port du Node lus dans WHATSAPP_BRIDGE_URL : la prod et le test
        // peuvent tourner sur le même serveur, chacun sur son port.
        $this->app->singleton(WhatsAppProcessManager::class, function () {
            $url = parse_url((string) config('services.whatsapp_bridge.url', 'http://127.0.0.1:3005'));

            return new WhatsAppProcessManager($url['host'] ?? '127.0.0.1', (int) ($url['port'] ?? 3005));
        });
    }

    public function boot(): void
    {
        // Pattern identique à tous les autres modules (voir CoursServiceProvider,
        // RHServiceProvider, etc.) - prefix('api') + middleware('api') est
        // OBLIGATOIRE ici : c'est ce groupe qui applique CORS et le retrait du
        // middleware stateful Sanctum configurés dans bootstrap/app.php.
        // Sans ->middleware('api'), les routes existent mais perdent CORS.
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                StartWhatsAppNode::class,
            ]);
        }

        // Volontairement AUCUN appel à WhatsAppProcessManager::startDetached()
        // ici. boot() tourne à chaque requête HTTP : démarrer le Node depuis
        // ce point créerait un risque de double-spawn et un process mal
        // détaché du worker PHP-FPM qui l'a lancé.
        //
        // Le démarrage automatique "Laravel démarre → Node démarre" est
        // garanti par Supervisor (autostart=true sur les deux programmes,
        // cf. supervisor/whatsapp-node.conf). Le filet de secours pour les
        // environnements sans Supervisor est la commande artisan
        // `whatsapp:node:start`, à invoquer une seule fois au déploiement/reboot.
    }
}

<?php

namespace App\Modules\WhatsApp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Gère le cycle de vie du process Node (whatsapp-service intégré).
 *
 * IMPORTANT — ce manager n'est JAMAIS appelé depuis WhatsAppServiceProvider::boot().
 * boot() s'exécute à chaque requête HTTP (sauf Octane) : y appeler start() créerait
 * un risque de double-spawn en cas de requêtes concurrentes au démarrage, et un
 * process lancé depuis un worker PHP-FPM peut mourir avec ce worker si mal détaché.
 *
 * Le démarrage réel "Laravel démarre → Node démarre" est garanti au niveau
 * infrastructure par Supervisor (autostart=true sur les deux programmes,
 * voir supervisor/whatsapp-node.conf fourni). Ce manager sert de :
 *   - vérification d'état (isRunning) pour l'onglet admin "Connexion"
 *   - filet de secours pour les environnements SANS Supervisor
 *     (appelé uniquement via `php artisan whatsapp:node:start`, jamais en HTTP)
 */
class WhatsAppProcessManager
{
    private const LOCK_KEY  = 'whatsapp-node-boot';
    private const LOCK_TTL  = 15; // secondes

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 3005,
    ) {
    }

    /**
     * Le Node répond-il sur son port ?
     * Utilisé par l'artisan command et par l'admin controller (onglet Connexion).
     */
    public function isRunning(): bool
    {
        $connection = @fsockopen($this->host, $this->port, $errno, $errstr, 0.5);
        if ($connection === false) {
            return false;
        }
        fclose($connection);
        return true;
    }

    /**
     * Démarre le process Node en mode détaché si :
     *  - il n'est pas déjà démarré
     *  - WA_AUTO_START=true
     *  - on obtient le verrou (évite le double-spawn concurrent)
     *
     * Ne lève JAMAIS d'exception vers l'appelant — toute erreur est logguée.
     * Retourne true si un démarrage a été tenté, false sinon (déjà up, verrou
     * pris ailleurs, ou auto-start désactivé).
     *
     * N'est appelé QUE depuis la commande artisan whatsapp:node:start,
     * jamais depuis un contexte web.
     */
    public function startDetached(): bool
    {
        if (!config('services.whatsapp_bridge.auto_start', false)) {
            Log::info('[WhatsApp] Démarrage automatique désactivé (WA_AUTO_START=false)');
            return false;
        }

        if ($this->isRunning()) {
            Log::info('[WhatsApp] Node déjà démarré, rien à faire');
            return false;
        }

        return Cache::lock(self::LOCK_KEY, self::LOCK_TTL)->get(function () {
            // Revérifier sous le verrou : une autre invocation a peut-être
            // démarré le process entre le premier check et l'obtention du verrou
            if ($this->isRunning()) {
                return false;
            }

            try {
                $nodePath = app_path('Modules/WhatsApp/node');
                $distFile = $nodePath . '/dist/index.js';

                if (!file_exists($distFile)) {
                    Log::warning("[WhatsApp] {$distFile} introuvable — le Node a-t-il été buildé ? (npm run build)");
                    return false;
                }

                $logFile      = storage_path('logs/whatsapp-node.log');
                $errorLogFile = storage_path('logs/whatsapp-node-error.log');

                // L'utilisation de proc_open() gardait la session SSH ouverte dans GitHub Actions
                // car des descripteurs de fichiers hérités restaient attachés au PTY.
                // En utilisant nohup + exec(), on détache totalement le processus.
                $isWindows = str_starts_with(PHP_OS_FAMILY, 'Win');
                if ($isWindows) {
                    $command = sprintf(
                        'cd %s && start /B node dist/index.js >> %s 2>> %s',
                        escapeshellarg($nodePath),
                        escapeshellarg($logFile),
                        escapeshellarg($errorLogFile)
                    );
                    pclose(popen($command, 'r'));
                } else {
                    $env = 'NODE_ENV=' . escapeshellarg(config('app.env')) . ' ';
                    $command = sprintf(
                        'cd %s && %s nohup setsid node dist/index.js >> %s 2>> %s < /dev/null &',
                        escapeshellarg($nodePath),
                        $env,
                        escapeshellarg($logFile),
                        escapeshellarg($errorLogFile)
                    );
                    exec($command);
                }

                Log::info('[WhatsApp] Process Node lancé en mode détaché');
                return true;

            } catch (\Throwable $e) {
                // Règle d'or : une erreur ici ne doit JAMAIS remonter et
                // interrompre le cycle de requête Laravel.
                Log::warning('[WhatsApp] Impossible de démarrer le Node : ' . $e->getMessage());
                return false;
            }
        });
    }
}

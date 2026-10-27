<?php

namespace App\Modules\WhatsApp\Console\Commands;

use App\Modules\WhatsApp\Services\WhatsAppProcessManager;
use Illuminate\Console\Command;

/**
 * php artisan whatsapp:node:start
 *
 * Filet de secours pour les environnements SANS Supervisor (ou en dev local).
 * En production avec Supervisor, ce n'est pas nécessaire : le programme
 * whatsapp-node y est démarré directement avec autostart=true.
 *
 * À appeler UNE FOIS au déploiement/reboot (ex: hook systemd, script de
 * démarrage), jamais depuis une requête HTTP.
 */
class StartWhatsAppNode extends Command
{
    protected $signature = 'whatsapp:node:start';
    protected $description = 'Démarre le service Node WhatsApp s\'il n\'est pas déjà en cours d\'exécution (filet de secours sans Supervisor)';

    public function handle(WhatsAppProcessManager $manager): int
    {
        if ($manager->isRunning()) {
            $this->info('Le service WhatsApp Node est déjà démarré.');
            return self::SUCCESS;
        }

        $started = $manager->startDetached();

        if ($started) {
            $this->info('Service WhatsApp Node démarré en mode détaché.');
            return self::SUCCESS;
        }

        $this->warn('Le service WhatsApp Node n\'a pas pu être démarré (voir storage/logs/whatsapp-node-error.log). Laravel continue de fonctionner normalement.');
        return self::FAILURE;
    }
}

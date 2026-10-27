<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\StageWhatsAppGroupService;
use Illuminate\Console\Command;

/** Groupe WhatsApp annuel : ajoute les stagiaires qui commencent, retire ceux qui ont fini depuis une semaine. */
class SyncStageWhatsAppGroup extends Command
{
    protected $signature = 'stages:sync-whatsapp-group';

    protected $description = 'Met à jour le groupe WhatsApp annuel des stagiaires';

    public function handle(StageWhatsAppGroupService $service): int
    {
        $s = $service->sync();
        $this->info("Ajoutés : {$s['added']} - invités : {$s['invited']} - retirés : {$s['removed']} - échecs : {$s['errors']}");

        return self::SUCCESS;
    }
}

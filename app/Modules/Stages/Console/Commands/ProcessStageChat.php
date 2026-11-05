<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\StageChatService;
use Illuminate\Console\Command;

/** Messagerie : rappels WhatsApp groupés et effacement des messages de plus de 7 jours. */
class ProcessStageChat extends Command
{
    protected $signature = 'stages:process-chat';

    protected $description = 'Messagerie de stage : rappels groupés des messages non lus, effacement après 7 jours';

    public function handle(StageChatService $chat): int
    {
        if (!$chat->ready()) {
            $this->info('Messagerie non installée (migration à passer).');

            return self::SUCCESS;
        }
        $s = $chat->tick();
        $this->info("Rappels : {$s['reminded']} - messages effacés : {$s['deleted']}");

        return self::SUCCESS;
    }
}

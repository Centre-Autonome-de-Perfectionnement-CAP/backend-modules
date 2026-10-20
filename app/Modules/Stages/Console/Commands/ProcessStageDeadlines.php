<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\StageRequestService;
use Illuminate\Console\Command;

/** Relance (J+3) puis expiration (J+7) des propositions sans réponse. */
class ProcessStageDeadlines extends Command
{
    protected $signature = 'stages:process-deadlines';

    protected $description = 'Relance et expiration des propositions de stage restées sans réponse';

    public function handle(StageRequestService $service): int
    {
        $stats = $service->processDeadlines();
        $this->info("Relances : {$stats['reminded']} — Expirations : {$stats['expired']}");

        return self::SUCCESS;
    }
}

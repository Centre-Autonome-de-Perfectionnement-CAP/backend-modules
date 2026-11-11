<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\InternInvitationService;
use App\Modules\Stages\Services\StageRequestService;
use Illuminate\Console\Command;

/**
 * Relance (J+3) puis expiration (J+7) des propositions sans réponse ;
 * binômes invités dont l'un ne s'est pas inscrit (l'autre poursuit seul).
 */
class ProcessStageDeadlines extends Command
{
    protected $signature = 'stages:process-deadlines';

    protected $description = 'Relance et expiration des propositions de stage restées sans réponse';

    public function handle(StageRequestService $service, InternInvitationService $invitations): int
    {
        $stats = $service->processDeadlines();
        $lonely = $invitations->closeLonelyPairs();
        $this->info("Relances : {$stats['reminded']} - Expirations : {$stats['expired']} - Binômes invités non formés : {$lonely}");

        return self::SUCCESS;
    }
}

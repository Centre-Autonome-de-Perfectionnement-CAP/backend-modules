<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\ReportService;
use Illuminate\Console\Command;

/** Rapports : rappels aux stagiaires (ouverture, dernier jour) ; récapitulatif quotidien au personnel. */
class ProcessStageReports extends Command
{
    protected $signature = 'stages:process-reports {--digest : envoie seulement le récapitulatif des rapports journaliers}';

    protected $description = 'Rappels de dépôt des rapports de stage et récapitulatif quotidien';

    public function handle(ReportService $service): int
    {
        $digestOnly = (bool) $this->option('digest');
        $s = $service->tick(null, !$digestOnly, $digestOnly);
        $this->info("Ouvertures : {$s['opened']} - derniers jours : {$s['closing']} - récapitulatif : {$s['digest']}");

        return self::SUCCESS;
    }
}

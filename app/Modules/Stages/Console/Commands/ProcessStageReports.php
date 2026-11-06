<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\ReportService;
use App\Modules\Stages\Services\StageNotifier;
use Illuminate\Console\Command;

/**
 * Rapports : rappels aux stagiaires (ouverture, dernier jour) ; en fin de
 * journée (--digest), récapitulatifs au personnel (rapports journaliers,
 * actions des superviseurs) ; toutes les 5 minutes (--comments), commentaires
 * de rapports regroupés par rafale.
 */
class ProcessStageReports extends Command
{
    protected $signature = 'stages:process-reports
        {--digest : envoie seulement les récapitulatifs de fin de journée}
        {--comments : envoie seulement les commentaires de rapports en attente}';

    protected $description = 'Rappels de dépôt des rapports de stage et récapitulatif quotidien';

    public function handle(ReportService $service, StageNotifier $notifier): int
    {
        if ($this->option('comments')) {
            $this->info('Commentaires signalés : ' . $service->flushComments());

            return self::SUCCESS;
        }
        $digestOnly = (bool) $this->option('digest');
        $s = $service->tick(null, !$digestOnly, $digestOnly);
        $this->info("Ouvertures : {$s['opened']} - mi-parcours : {$s['mid']} - derniers jours : {$s['closing']} - retards : {$s['late']} - récapitulatif : {$s['digest']}");
        if ($digestOnly) {
            $this->info('Actions des superviseurs récapitulées : ' . $notifier->sendSupervisorDigest());
        }

        return self::SUCCESS;
    }
}

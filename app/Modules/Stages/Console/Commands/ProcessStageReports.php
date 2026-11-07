<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\ReportService;
use App\Modules\Stages\Services\StageNotifier;
use App\Modules\Stages\Services\TaskService;
use Illuminate\Console\Command;

/**
 * Rapports et tâches : rappels aux stagiaires (ouverture, dernier jour,
 * tâche à rendre demain) ; en fin de journée (--digest), récapitulatifs
 * (rapports journaliers, actions des superviseurs, travaux publiés du jour) ;
 * toutes les 5 minutes (--comments), commentaires de rapports regroupés par rafale.
 */
class ProcessStageReports extends Command
{
    protected $signature = 'stages:process-reports
        {--digest : envoie seulement les récapitulatifs de fin de journée}
        {--comments : envoie seulement les commentaires de rapports en attente}';

    protected $description = 'Rappels de dépôt des rapports de stage et récapitulatif quotidien';

    public function handle(ReportService $service, StageNotifier $notifier, TaskService $tasks): int
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
            $this->info('Travaux publiés annoncés : ' . $tasks->announcePublished());
        } else {
            $this->info('Tâches à rendre demain signalées : ' . $tasks->remindDue());
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\InternActivationService;
use Illuminate\Console\Command;

/** Liens d'activation (jour du début, arrivée + 2 h), rappels, désistements. */
class ProcessStageActivations extends Command
{
    protected $signature = 'stages:process-activations';

    protected $description = "Envoie les liens d'activation de l'espace stagiaire, les rappels et constate les désistements";

    public function handle(InternActivationService $service): int
    {
        $s = $service->tick();
        $this->info("Liens : {$s['sent']} — rappels : {$s['reminded']} — désistements : {$s['withdrawn']}");

        return self::SUCCESS;
    }
}

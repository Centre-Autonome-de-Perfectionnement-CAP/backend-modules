<?php

namespace App\Modules\Stages\Console\Commands;

use App\Modules\Stages\Services\AttestationService;
use Illuminate\Console\Command;

/** Attestations : préparation à J-7 et relances (responsable, secrétariat). */
class ProcessStageAttestations extends Command
{
    protected $signature = 'stages:process-attestations';

    protected $description = 'Prépare les attestations de stage (J-7) et relance les attestations en attente';

    public function handle(AttestationService $service): int
    {
        $s = $service->tick();
        $this->info("Préparées : {$s['prepared']} — relances : {$s['reminded']}");

        return self::SUCCESS;
    }
}

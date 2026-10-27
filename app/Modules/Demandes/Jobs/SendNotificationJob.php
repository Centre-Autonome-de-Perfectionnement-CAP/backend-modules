<?php

namespace App\Modules\Demandes\Jobs;

/**
 * Compatibilité : l'implémentation a été déplacée dans
 * App\Modules\Core\Jobs\SendNotificationJob (partagée par tous les modules).
 *
 * Cette classe reste pour deux raisons :
 *   - les jobs déjà sérialisés dans la table `jobs` référencent ce nom ;
 *   - le code Demandes existant continue d'appeler SendNotificationJob::dispatch().
 */
class SendNotificationJob extends \App\Modules\Core\Jobs\SendNotificationJob
{
}

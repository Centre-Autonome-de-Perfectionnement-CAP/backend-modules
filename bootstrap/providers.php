<?php

return [
    App\Modules\Core\Providers\CoreServiceProvider::class,
    App\Modules\Auth\Providers\AuthServiceProvider::class,
    App\Modules\Inscription\Providers\InscriptionServiceProvider::class,
    App\Modules\EmploiDuTemps\Providers\EmploiDuTempsServiceProvider::class,
    App\Modules\Finance\Providers\FinanceServiceProvider::class,
    App\Modules\RH\Providers\RHServiceProvider::class,
    App\Modules\Stockage\Providers\StockageServiceProvider::class,
    App\Modules\Contact\Providers\ContactServiceProvider::class,
    App\Modules\Cours\Providers\CoursServiceProvider::class,
    App\Modules\Notes\Providers\NotesServiceProvider::class,
    App\Modules\Soutenance\Providers\SoutenanceServiceProvider::class,
];

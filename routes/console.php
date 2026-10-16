<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('demandes:send-reminders')
    ->weekdays()
    ->dailyAt('10:00')
    ->timezone('Africa/Porto-Novo');

// Filet de sécurité des notifications : si aucun worker ne tourne (crash,
// redémarrage serveur), le scheduler vide la file chaque minute. Sans effet
// gênant quand les workers sont actifs (withoutOverlapping, arrêt à vide).
Schedule::command('queue:work --queue=default,emails --stop-when-empty --sleep=1 --tries=3 --timeout=85 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

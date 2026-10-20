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

// Stages : relance (J+3) puis expiration (J+7) des propositions sans réponse.
// Tous les jours : ces messages vont aux demandeurs, pas au personnel.
Schedule::command('stages:process-deadlines')
    ->dailyAt('09:00')
    ->timezone('Africa/Porto-Novo')
    ->withoutOverlapping(30);

// Stages : groupe WhatsApp annuel (ajout des stagiaires qui commencent,
// retrait une semaine après la fin).
Schedule::command('stages:sync-whatsapp-group')
    ->dailyAt('10:30')
    ->timezone('Africa/Porto-Novo')
    ->withoutOverlapping(60);

// Filet de sécurité des notifications : si aucun worker ne tourne (crash,
// redémarrage serveur), le scheduler vide la file chaque minute. Sans effet
// gênant quand les workers sont actifs (withoutOverlapping, arrêt à vide).
Schedule::command('queue:work --queue=default,emails --stop-when-empty --sleep=1 --tries=3 --timeout=85 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

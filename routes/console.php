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

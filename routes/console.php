<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ежедневные уведомления в конце рабочего дня (18:00 по Ашхабаду)
Schedule::command('notifications:end-of-day')
    ->timezone('Asia/Ashgabat')
    ->dailyAt('18:00')
    ->weekdays() // Только с понедельника по пятницу
    ->withoutOverlapping()
    ->runInBackground();

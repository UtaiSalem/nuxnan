<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gamification Schedules
Schedule::job(new \App\Jobs\RefreshLeaderboardCache)->dailyAt('03:00');
Schedule::job(new \App\Jobs\ResetDailyQuests)->dailyAt('00:00');
Schedule::command('typing:generate-daily')->dailyAt('00:05');

// Typing Tournaments
Schedule::command('typing:create-weekly-tournament')->weeklyOn(0, '23:50'); // Every Sunday at 23:50
Schedule::command('typing:finalize-tournaments')->hourly();

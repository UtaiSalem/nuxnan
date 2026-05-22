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

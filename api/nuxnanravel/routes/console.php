<?php

use App\Jobs\RefreshLeaderboardCache;
use App\Jobs\ResetDailyQuests;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gamification Schedules
Schedule::job(new RefreshLeaderboardCache)->dailyAt('03:00');
Schedule::job(new ResetDailyQuests)->dailyAt('00:00');
Schedule::command('typing:generate-daily')->dailyAt('00:05');

// Typing Tournaments
Schedule::command('typing:create-weekly-tournament')->weeklyOn(0, '23:50'); // Every Sunday at 23:50
Schedule::command('typing:finalize-tournaments')->hourly();

// Pre-create cycles for gamification
Schedule::command('gamification:init-cycles')->weeklyOn(0, '00:01'); // Sunday midnight
Schedule::command('gamification:init-cycles')->monthlyOn(1, '00:01'); // 1st of month

// Wallet ledger reconciliation — daily money-in/out integrity check.
// The command exits non-zero when the ledger is unhealthy (wallet != ledger,
// negative balances, or money out exceeds money in), which triggers the alert.
Schedule::command('wallet:reconcile')
    ->dailyAt('03:30')
    ->appendOutputTo(storage_path('logs/wallet-reconcile.log'))
    ->onFailure(function () {
        Log::critical('[wallet:reconcile] ledger integrity check FAILED — investigate before processing payouts.');
    });

Schedule::command('course-points:cleanup-reservations')->everyFiveMinutes();
Schedule::command('reconcile:all --emit-risk')->dailyAt('03:00')->timezone('Asia/Bangkok');

// Telescope — auto-prune entries older than 48 hours to prevent DB bloat.
Schedule::command('telescope:prune --hours=48')->dailyAt('04:00');

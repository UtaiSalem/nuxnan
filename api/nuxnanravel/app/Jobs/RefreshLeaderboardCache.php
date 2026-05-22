<?php

namespace App\Jobs;

use App\Services\GamificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshLeaderboardCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GamificationService $service): void
    {
        // Refresh common leaderboard types
        $types = ['points', 'weekly', 'monthly', 'streak', 'achievements'];
        
        foreach ($types as $type) {
            // We can't easily force refresh Cache::remember without the key,
            // but we can call the service which will update it if expired,
            // or we could use Cache::forget first.
            \Illuminate\Support\Facades\Cache::forget("leaderboard_{$type}_1_20");
            $service->getLeaderboard($type, 1, 20);
        }
    }
}

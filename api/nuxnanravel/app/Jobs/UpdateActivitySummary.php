<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ActivitySummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateActivitySummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user, public string $date)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ActivitySummaryService $service): void
    {
        $service->updateDailySummary($this->user, $this->date);
    }
}

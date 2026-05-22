<?php

namespace App\Jobs;

use App\Models\UserUsageEvent;
use App\Services\GamificationRuleEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUsageEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public UserUsageEvent $event)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GamificationRuleEngine $engine): void
    {
        $engine->evaluate($this->event);
    }
}

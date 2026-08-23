<?php

namespace App\Console\Commands;

use App\Services\Election\ElectionStationService;
use Illuminate\Console\Command;

class ExpireStaleElectionReceipts extends Command
{
    protected $signature = 'elections:expire-stale-receipts';

    protected $description = 'Expire stale election ballot receipts.';

    public function handle(ElectionStationService $service): int
    {
        $expired = $service->expireStaleAll();
        $this->info("Expired {$expired} stale election receipts.");

        return self::SUCCESS;
    }
}

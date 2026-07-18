<?php

namespace App\Console\Commands;

use App\Services\FraudDetectionService;
use Illuminate\Console\Command;

class RiskScanCommand extends Command
{
    protected $signature = 'risk:scan {--window=24 : lookback window in hours for time-windowed scans}';

    protected $description = 'Run all fraud / integrity scans and open RiskEvents for anything suspicious';

    public function handle(FraudDetectionService $fraud): int
    {
        $window = (int) $this->option('window');

        $this->info('Scanning donation velocity...');
        $this->line('  created: '.$fraud->scanDonationVelocity(60, 5));

        $this->info('Scanning self-donation clusters...');
        $this->line('  created: '.$fraud->scanSelfDonationCluster(7, 3));

        $this->info('Scanning ad fraud (failed deliveries)...');
        $this->line('  created: '.$fraud->scanAdFraud($window));

        $this->info('Scanning ad revenue policy integrity...');
        $this->line('  created: '.$fraud->scanAdRevenuePolicy($window));

        $this->info('Scanning academy negative balances...');
        $this->line('  created: '.$fraud->scanAcademyNegativeBalance());

        $this->info('Risk scan complete.');

        return self::SUCCESS;
    }
}

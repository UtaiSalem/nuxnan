<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Console\Command;

class RegenerateCouponQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupons:regenerate-qr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate QR codes for all coupons';

    /**
     * Execute the console command.
     */
    public function handle(CouponService $couponService)
    {
        $this->info('Starting QR code regeneration...');

        $coupons = Coupon::all();
        $total = $coupons->count();
        $bar = $this->output->createProgressBar($total);

        $success = 0;
        $failed = 0;

        foreach ($coupons as $coupon) {
            try {
                $couponService->generateQRCode($coupon);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to generate QR for coupon {$coupon->code}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("Completed! Generated: {$success}, Failed: {$failed}");
        
        if ($failed > 0) {
            return 1;
        }
        
        return 0;
    }
}

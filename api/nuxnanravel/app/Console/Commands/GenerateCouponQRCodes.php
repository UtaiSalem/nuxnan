<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coupon;
use App\Services\CouponService;

class GenerateCouponQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupons:generate-qr {--all : Regenerate QR codes for all coupons} {--missing : Only generate for coupons without QR codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate QR codes for coupons';

    /**
     * Execute the console command.
     */
    public function handle(CouponService $couponService)
    {
        $query = Coupon::query();

        if ($this->option('missing')) {
            // Check if QR file exists (since we removed qr_code_path column)
            $this->info('Generating QR codes for coupons without QR files...');
            $coupons = $query->get()->filter(function ($coupon) {
                $path = storage_path('app/public/qr-codes/' . $coupon->coupon_code . '.svg');
                return !file_exists($path);
            });
        } elseif ($this->option('all')) {
            $this->info('Regenerating QR codes for all coupons with new Universal format (COUPON:code)...');
            $coupons = $query->get();
        } else {
            // Default: regenerate all with new format
            $this->info('Regenerating QR codes for all coupons with new Universal format (COUPON:code)...');
            $coupons = $query->get();
        }

        $count = $coupons->count();

        if ($count === 0) {
            $this->info('No coupons found that need QR codes.');
            return 0;
        }

        $this->info("Found {$count} coupons to process.");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($coupons as $coupon) {
            try {
                $couponService->generateQRCode($coupon);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("\nFailed to generate QR for coupon {$coupon->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Completed! Success: {$success}, Failed: {$failed}");

        return 0;
    }
}

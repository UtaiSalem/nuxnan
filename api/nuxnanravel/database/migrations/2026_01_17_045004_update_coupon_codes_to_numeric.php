<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert existing coupon codes to 8-digit numeric format.
     */
    public function up(): void
    {
        // Get all coupons and update their codes to 8-digit numeric
        $coupons = DB::table('coupons')->get();
        $usedCodes = [];

        foreach ($coupons as $coupon) {
            // Generate unique 8-digit numeric code
            do {
                $newCode = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while (in_array($newCode, $usedCodes) || DB::table('coupons')->where('coupon_code', $newCode)->where('id', '!=', $coupon->id)->exists());

            $usedCodes[] = $newCode;

            DB::table('coupons')
                ->where('id', $coupon->id)
                ->update(['coupon_code' => $newCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as original codes are lost
    }
};

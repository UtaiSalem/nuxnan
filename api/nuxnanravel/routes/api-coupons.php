<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CouponController;

/*
|--------------------------------------------------------------------------
| Coupon System Routes
|--------------------------------------------------------------------------
|
| Routes for coupon creation, redemption, and management
|
*/

Route::middleware(['auth:api'])->prefix('coupons')->group(function () {
    // Create a new coupon
    Route::post('/', [CouponController::class, 'create'])->name('coupons.create');

    // Get user's coupons
    Route::get('/', [CouponController::class, 'index'])->name('coupons.index');

    // Get coupon statistics
    Route::get('/statistics', [CouponController::class, 'statistics'])->name('coupons.statistics');

    // Get coupon details
    Route::get('/{id}', [CouponController::class, 'show'])->name('coupons.show');

    // Get printable coupon data
    Route::get('/{id}/print', [CouponController::class, 'print'])->name('coupons.print');

    // Redeem a coupon by code
    Route::post('/redeem', [CouponController::class, 'redeem'])->name('coupons.redeem');

    // Cancel a coupon
    Route::post('/{id}/cancel', [CouponController::class, 'cancel'])->name('coupons.cancel');
});

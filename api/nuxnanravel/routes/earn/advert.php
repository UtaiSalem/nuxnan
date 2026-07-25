<?php

use App\Http\Controllers\AdvertController;
use App\Http\Controllers\Api\Campaign\AdDeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('/advertises/widget', [AdvertController::class, 'widget'])->name('advertises.widget');
Route::get('/advertises', [AdvertController::class, 'index'])->name('advertises.index');
Route::get('/advertises/more', [AdvertController::class, 'getMoreAdvertisings'])->name('advertises.more');

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified'])->group(function () {

    Route::get('/advertises/create', [AdvertController::class, 'create'])->name('advertises.create');
    Route::post('/advertises', [AdvertController::class, 'store'])->name('advertises.store');
    Route::get('/advertises/{advert}/slip', [AdvertController::class, 'downloadSlip'])->name('advertises.slip');
    Route::post('/advertises/{advert}/view', [AdvertController::class, 'view'])->name('advertises.view');
    Route::post('/adverts/{advert}/deliveries/start', [AdDeliveryController::class, 'start']);
    Route::post('/ad-deliveries/{delivery}/heartbeat', [AdDeliveryController::class, 'heartbeat']);
    Route::post('/ad-deliveries/{delivery}/complete', [AdDeliveryController::class, 'complete']);

    Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/advertises')->group(function () {
        Route::get('/', [AdvertController::class, 'advertisesIndex'])->name('admin.advertises.index');
        Route::patch('/{advert}/approve', [AdvertController::class, 'approve'])->name('admin.advertises.approve');
        Route::patch('/{advert}/reject', [AdvertController::class, 'reject'])->name('admin.advertises.reject');
    });

});

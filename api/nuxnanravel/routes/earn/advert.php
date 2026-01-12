<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Shared\SupportController;
use App\Http\Controllers\AdvertController;


Route::get('/advertises/widget', [AdvertController::class, 'widget'])->name('advertises.widget');
Route::get('/advertises', [AdvertController::class, 'index'])->name('advertises.index');
Route::get('/advertises/more', [AdvertController::class, 'getMoreAdvertisings'])->name('advertises.more');

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified',])->group(function () {
    
    Route::get('/advertises/create', [AdvertController::class, 'create'])->name('advertises.create');
    Route::post('/advertises', [AdvertController::class, 'store'])->name('advertises.store');
    Route::post('/supports/plearnd', [SupportController::class, 'storePlearndSupport'])->name('support.store.plearnd');
    Route::post('/advertises/{advert}/view', [AdvertController::class, 'view'])->name('advertises.view');

    Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/advertises')->group(function () {
        Route::get('/', [AdvertController::class, 'advertisesIndex'])->name('admin.advertises.index');
        Route::patch('/{advert}/approve', [AdvertController::class, 'approve'])->name('admin.advertises.approve');
        Route::patch('/{advert}/reject', [AdvertController::class, 'reject'])->name('admin.advertises.reject');  
    });
        
});


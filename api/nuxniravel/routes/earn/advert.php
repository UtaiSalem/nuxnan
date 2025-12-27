<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Shared\SupportController;
use App\Http\Controllers\AdvertController;


Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified',])->group(function () {
    
    Route::get('/supports/widget', [SupportController::class, 'widget'])->name('supports.widget');
    Route::get('/supports', [SupportController::class, 'index'])->name('supports.index');
    Route::get('/supports/advertises/more', [SupportController::class, 'getMoreAdvertisings'])->name('supports.advertises.getmore');
    Route::get('/supports/advertises/create', [SupportController::class, 'create'])->name('support.advert.create');
    Route::post('/supports/advertise', [SupportController::class, 'storeAdvertise'])->name('support.store.advertise');
    Route::post('/supports/advertises/{advert}/view', [SupportController::class, 'viewAdvertise'])->name('support.advertises.view');

    Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/supports/advertises')->group(function () {
        Route::get('/', [SupportController::class, 'advertisesIndex'])->name('admin.support.advert.index');
        Route::patch('/{advert}/approve', [SupportController::class, 'approveAdvertise'])->name('admin.support.advert.approve');
        Route::patch('/{advert}/reject', [SupportController::class, 'rejectAdvertise'])->name('admin.support.advert.reject');  
    });
        
});


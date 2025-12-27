<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Earn\DonateController;


Route::get('/supports/donates/create', [DonateController::class, 'create'])->name('support.donate.create');
Route::post('/supports/donates', [DonateController::class, 'store'])->name('support.donate.store');
Route::get('/supports/donates/donor/{user:personal_code}', [DonateController::class, 'getDonor'])->name('donate.get-donor');

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/supports/donates')->group(function () {
    Route::get('/', [DonateController::class, 'index'])->name('admin.support.donate.index');
    Route::get('/more', [DonateController::class, 'fetch_more_donates'])->name('admin.support.donate.fetch-more-donates');
    Route::patch('/{donate}/recieve', [DonateController::class, 'recieve'])->name('admin.support.donate.recieve');
    Route::patch('/{donate}/reject', [DonateController::class, 'reject'])->name('admin.support.donate.reject');

});

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified',])->group(function () {
    Route::get('/donates/widget', [DonateController::class, 'widget'])->name('donates.widget');
    Route::get('/donates/{donate}/get-donate', [DonateController::class, 'getDonate'])->name('donate.get-donate');
    Route::get('/donates', [DonateController::class, 'allAvailableDonates'])->name('donates.list');
});


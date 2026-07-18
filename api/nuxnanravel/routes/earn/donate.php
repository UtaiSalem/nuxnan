<?php

use App\Http\Controllers\Api\Courses\CourseDonationController;
use App\Http\Controllers\Api\Earn\DonateController;
use App\Http\Controllers\Api\PlearndAdmin\CourseDonationAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::post('/courses/{course}/donations/points', [CourseDonationController::class, 'storePoint'])->name('course.donation.point');
    Route::post('/courses/{course}/donations/cash', [CourseDonationController::class, 'storeCash'])->name('course.donation.cash');
    Route::get('/me/course-donations', [CourseDonationController::class, 'mine']);
    Route::get('/courses/{course}/donations', [CourseDonationController::class, 'showForCourse']);
});
Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/course-donations')->group(function () {
    Route::get('/', [CourseDonationAdminController::class, 'index']);
    Route::patch('/{donation}/approve', [CourseDonationAdminController::class, 'approve']);
    Route::patch('/{donation}/reject', [CourseDonationAdminController::class, 'reject']);
});

// Public routes for creating donations (anonymous allowed)
Route::get('/supports/donates/create', [DonateController::class, 'create'])->name('support.donate.create');
Route::post('/supports/donates', [DonateController::class, 'store'])->name('support.donate.store');
Route::get('/supports/donates/donor/{user:personal_code}', [DonateController::class, 'getDonor'])->name('donate.get-donor');

// Public endpoint for viewing available donates (no auth required)
Route::get('/donates/available', [DonateController::class, 'allAvailableDonates'])->name('donates.available');

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/supports/donates')->group(function () {
    Route::get('/', [DonateController::class, 'index'])->name('admin.support.donate.index');
    Route::post('/bulk-review', [DonateController::class, 'bulkReview'])->name('admin.support.donate.bulk');
    Route::get('/{donate}', [DonateController::class, 'show'])->name('admin.support.donate.show');
    Route::patch('/{donate}', [DonateController::class, 'update'])->name('admin.support.donate.update');
    Route::delete('/{donate}', [DonateController::class, 'destroy'])->name('admin.support.donate.destroy');
    Route::patch('/{donate}/receive', [DonateController::class, 'receive'])->name('admin.support.donate.receive');
    Route::patch('/{donate}/recieve', [DonateController::class, 'receive']);
    Route::patch('/{donate}/reject', [DonateController::class, 'reject'])->name('admin.support.donate.reject');
});

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/donates/widget', [DonateController::class, 'widget'])->name('donates.widget');
    Route::get('/donates/{donate}/get-donate', [DonateController::class, 'getDonate'])->name('donate.get-donate');
    Route::get('/donates/history', [DonateController::class, 'history'])->name('donates.history');
    Route::get('/donates', [DonateController::class, 'allAvailableDonates'])->name('donates.list');
});

<?php

use App\Http\Controllers\Api\Academies\AcademyDonationController;
use App\Http\Controllers\Api\Academies\AcademyAllocationController;
use App\Http\Controllers\Api\Courses\CourseDonationController;
use App\Http\Controllers\Api\Courses\CoursePointWithdrawalController;
use App\Http\Controllers\Api\Earn\DonateController;
use App\Http\Controllers\Api\PlearndAdmin\AcademyDonationAdminController;
use App\Http\Controllers\Api\PlearndAdmin\CourseDonationAdminController;
use App\Http\Controllers\Api\PlearndAdmin\CoursePointWithdrawalAdminController;
use App\Http\Controllers\Api\PlearndAdmin\RevenueSharePolicyController;
use App\Http\Controllers\Api\PlearndAdmin\RiskEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::post('/courses/{course}/donations/points', [CourseDonationController::class, 'storePoint'])->name('course.donation.point');
    Route::post('/courses/{course}/donations/cash', [CourseDonationController::class, 'storeCash'])->name('course.donation.cash');
    Route::get('/me/course-donations', [CourseDonationController::class, 'mine']);
    Route::get('/courses/{course}/donations', [CourseDonationController::class, 'showForCourse']);
    Route::post('/academies/{academy}/donations/points', [AcademyDonationController::class, 'storePoint']);
    Route::post('/academies/{academy}/donations/cash', [AcademyDonationController::class, 'storeCash']);
    Route::get('/me/academy-donations', [AcademyDonationController::class, 'mine']);
    Route::get('/academies/{academy}/donations', [AcademyDonationController::class, 'showForAcademy']);
    Route::post('/academies/{academy}/allocations', [AcademyAllocationController::class, 'store']);
    Route::get('/academies/{academy}/allocations', [AcademyAllocationController::class, 'index']);
    Route::post('/courses/{course}/withdrawals', [CoursePointWithdrawalController::class, 'store']);
    Route::get('/courses/{course}/withdrawals', [CoursePointWithdrawalController::class, 'index']);
    Route::post('/course-withdrawals/{withdrawal}/cancel', [CoursePointWithdrawalController::class, 'cancel']);
});
Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/academy-donations')->group(function () {
    Route::get('/', [AcademyDonationAdminController::class, 'index']);
    Route::get('/{donation}', [AcademyDonationAdminController::class, 'show']);
    Route::patch('/{donation}/approve', [AcademyDonationAdminController::class, 'approve']);
    Route::patch('/{donation}/reject', [AcademyDonationAdminController::class, 'reject']);
});
Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/course-withdrawals')->group(function () {
    Route::get('/', [CoursePointWithdrawalAdminController::class, 'index']);
    Route::get('/{withdrawal}', [CoursePointWithdrawalAdminController::class, 'show']);
    Route::patch('/{withdrawal}/review', [CoursePointWithdrawalAdminController::class, 'review']);
    Route::patch('/{withdrawal}/approve', [CoursePointWithdrawalAdminController::class, 'approve']);
    Route::patch('/{withdrawal}/reject', [CoursePointWithdrawalAdminController::class, 'reject']);
    Route::patch('/{withdrawal}/mark-paid', [CoursePointWithdrawalAdminController::class, 'markPaid']);
});
Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/course-donations')->group(function () {
    Route::get('/', [CourseDonationAdminController::class, 'index']);
    Route::patch('/{donation}/approve', [CourseDonationAdminController::class, 'approve']);
    Route::patch('/{donation}/reject', [CourseDonationAdminController::class, 'reject']);
});
Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/revenue-share-policies')->group(function () {
    Route::get('/', [RevenueSharePolicyController::class, 'index']);
    Route::post('/', [RevenueSharePolicyController::class, 'store']);
    Route::patch('/{policy}', [RevenueSharePolicyController::class, 'update']);
    Route::get('/{policy}/usage', [RevenueSharePolicyController::class, 'usage']);
});
Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/risk-events')->group(function () {
    Route::get('/', [RiskEventController::class, 'index']);
    Route::get('/{risk}', [RiskEventController::class, 'show']);
    Route::patch('/{risk}/acknowledge', [RiskEventController::class, 'acknowledge']);
    Route::patch('/{risk}/resolve', [RiskEventController::class, 'resolve']);
    Route::patch('/{risk}/dismiss', [RiskEventController::class, 'dismiss']);
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

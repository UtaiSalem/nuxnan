<?php

use App\Http\Controllers\Api\Academies\AcademyAllocationController;
use App\Http\Controllers\Api\Academies\AcademyClaimController;
use App\Http\Controllers\Api\Academies\AcademyDonationController;
use App\Http\Controllers\Api\Academies\AcademyPointWithdrawalController;
use App\Http\Controllers\Api\Courses\CourseClaimController;
use App\Http\Controllers\Api\Courses\CourseDonationController;
use App\Http\Controllers\Api\Courses\CoursePointWithdrawalController;
use App\Http\Controllers\Api\Earn\DonateController;
use App\Http\Controllers\Api\PlearndAdmin\AcademyDonationAdminController;
use App\Http\Controllers\Api\PlearndAdmin\AcademyPointWithdrawalAdminController;
use App\Http\Controllers\Api\PlearndAdmin\CourseDonationAdminController;
use App\Http\Controllers\Api\PlearndAdmin\CoursePointWithdrawalAdminController;
use App\Http\Controllers\Api\PlearndAdmin\RevenueSharePolicyController;
use App\Http\Controllers\Api\PlearndAdmin\RiskEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::post('/courses/{course}/donations/points', [CourseDonationController::class, 'storePoint'])->name('course.donation.point');
    Route::get('/me/course-donations', [CourseDonationController::class, 'mine']);
    Route::get('/courses/{course}/donations', [CourseDonationController::class, 'showForCourse']);
    Route::get('/courses/{course}/donations/claimable', [CourseClaimController::class, 'claimable']);
    Route::post('/courses/{course}/donations/{donation}/claim', [CourseClaimController::class, 'claimFromDonation']);
    Route::post('/academies/{academy}/donations/points', [AcademyDonationController::class, 'storePoint']);
    Route::get('/me/academy-donations', [AcademyDonationController::class, 'mine']);
    Route::get('/academies/{academy}/donations', [AcademyDonationController::class, 'showForAcademy']);
    Route::get('/academies/{academy}/donations/claimable', [AcademyClaimController::class, 'claimable']);
    Route::post('/academies/{academy}/donations/{donation}/claim', [AcademyClaimController::class, 'claimFromDonation']);
    Route::post('/academies/{academy}/allocations', [AcademyAllocationController::class, 'store']);
    Route::get('/academies/{academy}/allocations', [AcademyAllocationController::class, 'index']);
    Route::post('/courses/{course}/withdrawals', [CoursePointWithdrawalController::class, 'store']);
    Route::get('/courses/{course}/withdrawals', [CoursePointWithdrawalController::class, 'index']);
    Route::post('/course-withdrawals/{withdrawal}/cancel', [CoursePointWithdrawalController::class, 'cancel']);
    Route::post('/academies/{academy}/withdrawals', [AcademyPointWithdrawalController::class, 'store']);
    Route::get('/academies/{academy}/withdrawals', [AcademyPointWithdrawalController::class, 'index']);
    Route::post('/academy-withdrawals/{withdrawal}/cancel', [AcademyPointWithdrawalController::class, 'cancel']);
});
Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified', 'plearnd_admin'])->prefix('/plearnd-admin/academy-withdrawals')->group(function () {
    Route::get('/', [AcademyPointWithdrawalAdminController::class, 'index']);
    Route::get('/{withdrawal}', [AcademyPointWithdrawalAdminController::class, 'show']);
    Route::patch('/{withdrawal}/review', [AcademyPointWithdrawalAdminController::class, 'review']);
    Route::patch('/{withdrawal}/approve', [AcademyPointWithdrawalAdminController::class, 'approve']);
    Route::patch('/{withdrawal}/reject', [AcademyPointWithdrawalAdminController::class, 'reject']);
    Route::patch('/{withdrawal}/mark-paid', [AcademyPointWithdrawalAdminController::class, 'markPaid']);
});
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/courses/{course}/donations/cash', [CourseDonationController::class, 'storeCash'])->name('course.donation.cash');
    Route::post('/academies/{academy}/donations/cash', [AcademyDonationController::class, 'storeCash']);
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
    Route::get('/{donate}/slip', [DonateController::class, 'downloadSlip'])->name('admin.support.donate.slip');
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

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PointsController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\AdminPointsController;
use App\Http\Controllers\Api\AdminWalletController;
use App\Http\Controllers\Api\Admin\DepositRequestController;

/*
|--------------------------------------------------------------------------
| Points & Wallet API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your points and wallet system.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Gamification Routes (no auth required)
Route::prefix('gamification')->group(function () {
    Route::get('/leaderboard/points', [GamificationController::class, 'getPointsLeaderboard'])->name('gamification.leaderboard.points');
    Route::get('/leaderboard/streak', [GamificationController::class, 'getStreakLeaderboard'])->name('gamification.leaderboard.streak');
    Route::get('/leaderboard/achievements', [GamificationController::class, 'getAchievementLeaderboard'])->name('gamification.leaderboard.achievements');
    Route::get('/leaderboard/level', [GamificationController::class, 'getLevelLeaderboard'])->name('gamification.leaderboard.level');
    Route::get('/leaderboard/summary', [GamificationController::class, 'getLeaderboardSummary'])->name('gamification.leaderboard.summary');
});

Route::middleware('auth:api')->group(function () {
    // Points Routes
    Route::prefix('points')->group(function () {
        Route::get('/balance', [PointsController::class, 'balance']);
        Route::post('/earn', [PointsController::class, 'earn']);
        Route::post('/spend', [PointsController::class, 'spend']);
        Route::post('/refund', [PointsController::class, 'refund']);
        Route::post('/transfer', [PointsController::class, 'transfer']);
        Route::post('/convert', [PointsController::class, 'convert']);
        Route::get('/transactions', [PointsController::class, 'transactions']);
        Route::get('/rules', [PointsController::class, 'rules']);
    });

    // Wallet Routes
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
        Route::post('/convert-points', [WalletController::class, 'convertPoints']);
        Route::post('/convert-to-points', [WalletController::class, 'convertToPoints']);
        Route::get('/transactions', [WalletController::class, 'transactions']);

        // Deposit Request Routes (User)
        Route::post('/deposit-request', [WalletController::class, 'createDepositRequest']);
        Route::get('/deposit-requests', [WalletController::class, 'getDepositRequests']);
        Route::delete('/deposit-requests/{id}', [WalletController::class, 'cancelDepositRequest']);

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            Route::post('/withdrawals/{id}/approve', [WalletController::class, 'approveWithdrawal']);
            Route::post('/withdrawals/{id}/reject', [WalletController::class, 'rejectWithdrawal']);
        });
    });

    // Gamification Routes (require auth)
    Route::prefix('gamification')->group(function () {
        Route::post('/login', [GamificationController::class, 'recordLogin']);
        Route::get('/streak', [GamificationController::class, 'getStreakInfo']);
        Route::get('/achievements', [GamificationController::class, 'getAchievements']);
        Route::get('/achievements/available', [GamificationController::class, 'getAvailableAchievements']);
        Route::get('/achievements/stats', [GamificationController::class, 'getAchievementStats']);
    });

    // Follower Routes
    Route::prefix('follow')->group(function () {
        Route::post('/toggle', [FollowController::class, 'toggleFollow']);
        Route::get('/is-following', [FollowController::class, 'isFollowing']);
        Route::get('/users/{userId}/followers', [FollowController::class, 'followers']);
        Route::get('/users/{userId}/following', [FollowController::class, 'following']);
        Route::get('/users/{userId}/stats', [FollowController::class, 'stats']);
    });

    // Rewards Routes
    Route::prefix('rewards')->group(function () {
        Route::get('/', [RewardController::class, 'index']);
        Route::get('/my', [RewardController::class, 'myRewards']);
        Route::get('/stats', [RewardController::class, 'stats']);
        Route::post('/redeem', [RewardController::class, 'redeem']);
        Route::get('/{id}', [RewardController::class, 'show']);
        Route::post('/{id}/claim', [RewardController::class, 'claim']);
        
        // Admin routes
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [RewardController::class, 'store']);
            Route::put('/{id}', [RewardController::class, 'update']);
            Route::delete('/{id}', [RewardController::class, 'destroy']);
        });
    });

    // Admin Routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Admin Points Routes
        Route::prefix('points')->group(function () {
            Route::get('/stats', [AdminPointsController::class, 'stats']);
            Route::get('/rules', [AdminPointsController::class, 'rules']);
            Route::post('/rules', [AdminPointsController::class, 'createRule']);
            Route::put('/rules/{id}', [AdminPointsController::class, 'updateRule']);
            Route::delete('/rules/{id}', [AdminPointsController::class, 'deleteRule']);
            Route::post('/users/{userId}/adjust', [AdminPointsController::class, 'adjustPoints']);
            Route::get('/users/{userId}/transactions', [AdminPointsController::class, 'userTransactions']);
            Route::get('/leaderboard', [AdminPointsController::class, 'leaderboard']);
            Route::get('/analytics', [AdminPointsController::class, 'analytics']);
        });

        // Admin Wallet Routes
        Route::prefix('wallet')->group(function () {
            Route::get('/stats', [AdminWalletController::class, 'stats']);
            Route::get('/withdrawals/pending', [AdminWalletController::class, 'pendingWithdrawals']);
            Route::post('/withdrawals/{transactionId}/approve', [AdminWalletController::class, 'approveWithdrawal']);
            Route::post('/withdrawals/{transactionId}/reject', [AdminWalletController::class, 'rejectWithdrawal']);
            Route::post('/users/{userId}/adjust', [AdminWalletController::class, 'adjustWallet']);
            Route::get('/users/{userId}/transactions', [AdminWalletController::class, 'userTransactions']);
            Route::get('/analytics', [AdminWalletController::class, 'analytics']);
        });

        // Admin Deposit Request Routes
        Route::prefix('deposit-requests')->group(function () {
            Route::get('/', [DepositRequestController::class, 'index']);
            Route::get('/{id}', [DepositRequestController::class, 'show']);
            Route::post('/{id}/approve', [DepositRequestController::class, 'approve']);
            Route::post('/{id}/reject', [DepositRequestController::class, 'reject']);
        });
    });
});

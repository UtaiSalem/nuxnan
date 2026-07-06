<?php

use App\Http\Controllers\Api\Learn\Academy\AcademyStoreController;
use App\Http\Controllers\Api\Learn\Academy\StoreCategoryController;
use App\Http\Controllers\Api\Learn\Academy\StoreOrderController;
use App\Http\Controllers\Api\Learn\Academy\StoreProductController;
use App\Http\Controllers\Api\Learn\Academy\StoreReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| School Store Routes
|--------------------------------------------------------------------------
| ระบบร้านค้าของโรงเรียน
| Prefix: /api/academies/{academy}/store
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api'])->prefix('/academies/{academy}/store')->group(function () {

    // ============================================================
    // Store Settings
    // ============================================================
    Route::get('/', [AcademyStoreController::class, 'show'])->name('api.academy.store.show');
    Route::post('/', [AcademyStoreController::class, 'store'])->name('api.academy.store.create');
    Route::patch('/', [AcademyStoreController::class, 'update'])->name('api.academy.store.update');
    Route::get('/stats', [AcademyStoreController::class, 'stats'])->name('api.academy.store.stats');
    Route::post('/logo', [AcademyStoreController::class, 'uploadLogo'])->name('api.academy.store.logo');
    Route::post('/banner', [AcademyStoreController::class, 'uploadBanner'])->name('api.academy.store.banner');

    // ============================================================
    // Categories
    // ============================================================
    Route::get('/categories', [StoreCategoryController::class, 'index'])->name('api.academy.store.categories.index');
    Route::post('/categories', [StoreCategoryController::class, 'store'])->name('api.academy.store.categories.store');
    Route::patch('/categories/{category}', [StoreCategoryController::class, 'update'])->name('api.academy.store.categories.update');
    Route::delete('/categories/{category}', [StoreCategoryController::class, 'destroy'])->name('api.academy.store.categories.destroy');

    // ============================================================
    // Products
    // ============================================================
    Route::get('/products', [StoreProductController::class, 'index'])->name('api.academy.store.products.index');
    Route::post('/products', [StoreProductController::class, 'store'])->name('api.academy.store.products.store');
    Route::get('/products/{product}', [StoreProductController::class, 'show'])->name('api.academy.store.products.show');
    Route::patch('/products/{product}', [StoreProductController::class, 'update'])->name('api.academy.store.products.update');
    Route::delete('/products/{product}', [StoreProductController::class, 'destroy'])->name('api.academy.store.products.destroy');
    Route::post('/products/{product}/images', [StoreProductController::class, 'uploadImages'])->name('api.academy.store.products.images.upload');
    Route::delete('/products/{product}/images', [StoreProductController::class, 'deleteImage'])->name('api.academy.store.products.images.delete');

    // Product Reviews
    Route::get('/products/{product}/reviews', [StoreReviewController::class, 'index'])->name('api.academy.store.products.reviews.index');
    Route::post('/products/{product}/reviews', [StoreReviewController::class, 'store'])->name('api.academy.store.products.reviews.store');
    Route::delete('/products/{product}/reviews/{review}', [StoreReviewController::class, 'destroy'])->name('api.academy.store.products.reviews.destroy');

    // ============================================================
    // Orders
    // ============================================================
    Route::get('/orders', [StoreOrderController::class, 'index'])->name('api.academy.store.orders.index');
    Route::get('/orders/my', [StoreOrderController::class, 'myOrders'])->name('api.academy.store.orders.my');
    Route::post('/orders', [StoreOrderController::class, 'store'])->name('api.academy.store.orders.store');
    Route::get('/orders/{order}', [StoreOrderController::class, 'show'])->name('api.academy.store.orders.show');
    Route::post('/orders/{order}/confirm', [StoreOrderController::class, 'confirm'])->name('api.academy.store.orders.confirm');
    Route::post('/orders/{order}/process', [StoreOrderController::class, 'process'])->name('api.academy.store.orders.process');
    Route::post('/orders/{order}/ready', [StoreOrderController::class, 'markReady'])->name('api.academy.store.orders.ready');
    Route::post('/orders/{order}/complete', [StoreOrderController::class, 'complete'])->name('api.academy.store.orders.complete');
    Route::post('/orders/{order}/cancel', [StoreOrderController::class, 'cancel'])->name('api.academy.store.orders.cancel');

    // ============================================================
    // Sales Report
    // ============================================================
    Route::get('/reports/sales', [StoreOrderController::class, 'salesReport'])->name('api.academy.store.reports.sales');
});

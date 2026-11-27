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
|
| SA-2b: เส้นฝั่งจัดการร้านใส่ `academy.permission:store.manage` แบบรายเส้น
| ห้ามย้ายไปแขวนที่กลุ่ม — กลุ่มนี้มีเส้นของผู้ซื้อปนอยู่ (ดู/สั่งซื้อ/รีวิว/ออเดอร์ของตัวเอง)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:api'])->prefix('/academies/{academy}/store')->group(function () {

    // ============================================================
    // Store Settings
    // ============================================================
    Route::get('/', [AcademyStoreController::class, 'show'])->name('api.academy.store.show');
    Route::post('/', [AcademyStoreController::class, 'store'])->middleware('academy.permission:store.manage')->name('api.academy.store.create');
    Route::patch('/', [AcademyStoreController::class, 'update'])->middleware('academy.permission:store.manage')->name('api.academy.store.update');
    Route::get('/stats', [AcademyStoreController::class, 'stats'])->middleware('academy.permission:store.manage')->name('api.academy.store.stats');
    Route::post('/logo', [AcademyStoreController::class, 'uploadLogo'])->middleware('academy.permission:store.manage')->name('api.academy.store.logo');
    Route::post('/banner', [AcademyStoreController::class, 'uploadBanner'])->middleware('academy.permission:store.manage')->name('api.academy.store.banner');

    // ============================================================
    // Categories
    // ============================================================
    Route::get('/categories', [StoreCategoryController::class, 'index'])->name('api.academy.store.categories.index');
    Route::post('/categories', [StoreCategoryController::class, 'store'])->middleware('academy.permission:store.manage')->name('api.academy.store.categories.store');
    Route::patch('/categories/{category}', [StoreCategoryController::class, 'update'])->middleware('academy.permission:store.manage')->name('api.academy.store.categories.update');
    Route::delete('/categories/{category}', [StoreCategoryController::class, 'destroy'])->middleware('academy.permission:store.manage')->name('api.academy.store.categories.destroy');

    // ============================================================
    // Products
    // ============================================================
    Route::get('/products', [StoreProductController::class, 'index'])->name('api.academy.store.products.index');
    Route::post('/products', [StoreProductController::class, 'store'])->middleware('academy.permission:store.manage')->name('api.academy.store.products.store');
    Route::get('/products/{product}', [StoreProductController::class, 'show'])->name('api.academy.store.products.show');
    Route::patch('/products/{product}', [StoreProductController::class, 'update'])->middleware('academy.permission:store.manage')->name('api.academy.store.products.update');
    Route::delete('/products/{product}', [StoreProductController::class, 'destroy'])->middleware('academy.permission:store.manage')->name('api.academy.store.products.destroy');
    Route::post('/products/{product}/images', [StoreProductController::class, 'uploadImages'])->middleware('academy.permission:store.manage')->name('api.academy.store.products.images.upload');
    Route::delete('/products/{product}/images', [StoreProductController::class, 'deleteImage'])->middleware('academy.permission:store.manage')->name('api.academy.store.products.images.delete');

    // Product Reviews (ผู้ซื้อ — การลบเช็คเจ้าของรีวิว/store.manage ในคอนโทรลเลอร์)
    Route::get('/products/{product}/reviews', [StoreReviewController::class, 'index'])->name('api.academy.store.products.reviews.index');
    Route::post('/products/{product}/reviews', [StoreReviewController::class, 'store'])->name('api.academy.store.products.reviews.store');
    Route::delete('/products/{product}/reviews/{review}', [StoreReviewController::class, 'destroy'])->name('api.academy.store.products.reviews.destroy');

    // ============================================================
    // Orders
    // ============================================================
    Route::get('/orders', [StoreOrderController::class, 'index'])->middleware('academy.permission:store.manage')->name('api.academy.store.orders.index');
    Route::get('/orders/my', [StoreOrderController::class, 'myOrders'])->name('api.academy.store.orders.my');
    Route::post('/orders', [StoreOrderController::class, 'store'])->name('api.academy.store.orders.store');
    // show/cancel: ผู้ซื้อเจ้าของออเดอร์ หรือผู้มี store.manage — เช็คในคอนโทรลเลอร์
    Route::get('/orders/{order}', [StoreOrderController::class, 'show'])->name('api.academy.store.orders.show');
    Route::post('/orders/{order}/confirm', [StoreOrderController::class, 'confirm'])->middleware('academy.permission:store.manage')->name('api.academy.store.orders.confirm');
    Route::post('/orders/{order}/process', [StoreOrderController::class, 'process'])->middleware('academy.permission:store.manage')->name('api.academy.store.orders.process');
    Route::post('/orders/{order}/ready', [StoreOrderController::class, 'markReady'])->middleware('academy.permission:store.manage')->name('api.academy.store.orders.ready');
    Route::post('/orders/{order}/complete', [StoreOrderController::class, 'complete'])->middleware('academy.permission:store.manage')->name('api.academy.store.orders.complete');
    Route::post('/orders/{order}/cancel', [StoreOrderController::class, 'cancel'])->name('api.academy.store.orders.cancel');

    // ============================================================
    // Sales Report
    // ============================================================
    Route::get('/reports/sales', [StoreOrderController::class, 'salesReport'])->middleware('academy.permission:store.manage')->name('api.academy.store.reports.sales');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QRCodeController;

/*
|--------------------------------------------------------------------------
| Universal QR Code API Routes
|--------------------------------------------------------------------------
|
| Routes for QR code parsing and execution
|
*/

Route::prefix('qr')->group(function () {
    // Get supported QR types (public)
    Route::get('/types', [QRCodeController::class, 'types'])->name('qr.types');
    
    // Parse QR code (authenticated)
    Route::middleware('auth:api')->group(function () {
        Route::post('/parse', [QRCodeController::class, 'parse'])->name('qr.parse');
        Route::post('/execute', [QRCodeController::class, 'execute'])->name('qr.execute');
    });
});

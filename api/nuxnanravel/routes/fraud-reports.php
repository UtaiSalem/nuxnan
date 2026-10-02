<?php

use App\Http\Controllers\Api\FraudReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fraud Report API Routes (member-facing)
|--------------------------------------------------------------------------
|
| Members report a fraudulent account here. Reports feed the admin review
| queue (routes/admin/admin.php -> fraud-reports), which can freeze the
| reported account's economy via the existing suspension flow.
|
*/

Route::middleware('auth:api')->prefix('fraud-reports')->group(function () {
    Route::post('/', [FraudReportController::class, 'store'])->name('fraud-reports.store');
    Route::get('/mine', [FraudReportController::class, 'mine'])->name('fraud-reports.mine');
});

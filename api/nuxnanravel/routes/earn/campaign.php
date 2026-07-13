<?php

use App\Http\Controllers\Api\Campaign\CampaignController;
use Illuminate\Support\Facades\Route;

Route::get('/campaigns/widget', [CampaignController::class, 'widget'])->name('campaigns.widget');
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
// Impressions are counted for everyone (including guests); the request/service handle a null viewer.
Route::post('/campaigns/{campaign}/impression', [CampaignController::class, 'impression'])->name('campaigns.impression');

Route::middleware(['auth:api', config('jetstream.auth_session')])->group(function () {
    Route::get('/campaigns/targets/academies', [CampaignController::class, 'targetAcademies'])->name('campaigns.targets.academies');
    Route::get('/campaigns/targets/courses', [CampaignController::class, 'targetCourses'])->name('campaigns.targets.courses');
});

Route::middleware(['auth:api', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::post('/campaigns/{campaign}/view', [CampaignController::class, 'view'])->name('campaigns.view');
    Route::get('/campaigns/manage', [CampaignController::class, 'manage'])->name('campaigns.manage');
    Route::get('/campaigns/admin', [CampaignController::class, 'adminIndex'])->name('campaigns.admin.index');
    Route::get('/campaigns/admin/audit-logs', [CampaignController::class, 'auditLogs'])->name('campaigns.admin.audit-logs');
    Route::patch('/campaigns/{campaign}/payment', [CampaignController::class, 'updatePaymentStatus'])->name('campaigns.payment.update');
    Route::get('/academies/{academy}/campaigns/manage', [CampaignController::class, 'academyManage'])->name('academies.campaigns.manage');
    Route::get('/courses/{course}/campaigns/manage', [CampaignController::class, 'courseManage'])->name('courses.campaigns.manage');
    Route::patch('/campaigns/{campaign}/review', [CampaignController::class, 'review'])->name('campaigns.review');
});

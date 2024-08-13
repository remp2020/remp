<?php

use Remp\CampaignModule\Http\Controllers\SegmentCacheController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Remp\CampaignModule\Http\Controllers\CampaignController;
use Remp\CampaignModule\Http\Controllers\BannerController;
use Remp\CampaignModule\Http\Controllers\ScheduleController;

Route::middleware('auth:api')->group(function() {
    Route::prefix('schedule')->group(function() {
        Route::post('{schedule}/start', [ScheduleController::class, 'start'])->name('api.schedule.start');
        Route::post('{schedule}/pause', [ScheduleController::class, 'pause'])->name('api.schedule.pause');
        Route::post('{schedule}/stop', [ScheduleController::class, 'stop'])->name('api.schedule.stop');
    });

    Route::post('banners/{banner}/one-time-display', [BannerController::class, 'oneTimeDisplay'])->name('api.banners.one_time_display');

    Route::apiResource('campaigns', CampaignController::class)->names('api.campaigns');
    Route::apiResource('banners', BannerController::class)->names('api.banners');
    Route::apiResource('schedule', ScheduleController::class)->names('api.schedule');

    Route::post('campaigns/toggle-active/{campaign}', [CampaignController::class, 'toggleActive'])->name('api.campaigns.toggle_active');

    Route::post(
        'segment-cache/provider/{segment_provider}/code/{segment_code}/add-user',
        [SegmentCacheController::class, 'addUserToCache']
    );
    Route::post(
        'segment-cache/provider/{segment_provider}/code/{segment_code}/remove-user',
        [SegmentCacheController::class, 'removeUserFromCache']
    );
});

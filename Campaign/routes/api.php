<?php

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

Route::middleware('auth:api')->group(function() {
    Route::prefix('schedule')->group(function() {
        Route::post('{schedule}/start', 'ScheduleController@start')->name('schedule.start');
        Route::post('{schedule}/pause', 'ScheduleController@pause')->name('schedule.pause');
        Route::post('{schedule}/stop', 'ScheduleController@stop')->name('schedule.stop');
    });

    Route::post('banners/{banner}/one-time-display', 'BannerController@oneTimeDisplay')->name('api.banners.one_time_display');

    Route::apiResource('campaigns', 'CampaignController');
    Route::apiResource('banners', 'BannerController');
    Route::apiResource('schedule', 'ScheduleController');

    Route::post('campaigns/toggle-active/{campaign}', 'CampaignController@toggleActive')->name('api.campaigns.toggle_active');

    Route::post(
        'segment-cache/provider/{segment_provider}/code/{segment_code}/add-user',
        'SegmentCacheController@addUserToCache'
    );
    Route::post(
        'segment-cache/provider/{segment_provider}/code/{segment_code}/remove-user',
        'SegmentCacheController@removeUserFromCache'
    );
});

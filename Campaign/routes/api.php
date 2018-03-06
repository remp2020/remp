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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->group(function() {
    Route::prefix('schedule')->group(function() {
        Route::post('{schedule}/start', 'ScheduleController@start')->name('schedule.start');
        Route::post('{schedule}/pause', 'ScheduleController@pause')->name('schedule.pause');
        Route::post('{schedule}/stop', 'ScheduleController@stop')->name('schedule.stop');
    });

    Route::apiResource('campaigns', 'CampaignController', [
        'names'=> [
            'update' => 'campaigns.api.update'
        ]
    ]);
    Route::apiResource('banners', 'BannerController');
    Route::apiResource('schedule', 'ScheduleController');

    Route::patch('api/campaigns/toggle-active/{campaign}', 'CampaignController@toggleActive')->name('campaigns.api.toggle_active');
});


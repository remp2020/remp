<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/error', 'AuthController@error')->name('sso.error');

Route::get('banners/preview/{uuid}', 'BannerController@preview')->name('banners.preview');
Route::get('campaigns/showtime', 'CampaignController@showtime')->name('campaigns.showtime');

Route::middleware('auth.jwt')->group(function () {
    Route::get('/', 'DashboardController@index');
    Route::get('dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('banners/json', 'BannerController@json')->name('banners.json');
    Route::get('banners/{sourceBanner}/copy', 'BannerController@copy')->name('banners.copy');
    Route::get('campaigns/json', 'CampaignController@json')->name('campaigns.json');
    Route::get('campaigns/{sourceCampaign}/copy', 'CampaignController@copy')->name('campaigns.copy');
    Route::get('campaigns/{campaign}/schedule/json', 'ScheduleController@json')->name('campaign.schedule.json');
    Route::get('schedule/json', 'ScheduleController@json')->name('schedule.json');
    Route::post('schedule/{schedule}/start', 'ScheduleController@start')->name('schedule.start');
    Route::post('schedule/{schedule}/pause', 'ScheduleController@pause')->name('schedule.pause');
    Route::post('schedule/{schedule}/stop', 'ScheduleController@stop')->name('schedule.stop');

    Route::post('campaigns/validate', 'CampaignController@validateForm')->name('campaigns.validateForm');
    Route::post('banners/validate', 'BannerController@validateForm')->name('banners.validateForm');

    Route::get('campaigns/{campaign}/stats', 'CampaignController@stats')->name('campaigns.stats');

    Route::get('auth/logout', 'AuthController@logout')->name('auth.logout');

    // campaign count + histogram
    Route::post('campaigns/{campaign}/stats/{type}/count', 'StatsController@campaignStatsCount');
    Route::post('campaigns/{campaign}/stats/histogram', 'StatsController@campaignStatsHistogram');

    // variant count + histogram
    Route::post('campaigns/stats/variant/{variant}/histogram', 'StatsController@variantStatsHistogram');
    Route::post('campaigns/stats/variant/{variant}/{type}/count', 'StatsController@variantStatsCount');

    // campaign payments
    Route::post('campaigns/{campaign}/payment/stats/step/{step}/count', 'StatsController@campaignPaymentStatsCount');
    Route::post('campaigns/{campaign}/payment/stats/step/{step}/sum', 'StatsController@campaignPaymentStatsSum');

    // variant payments
    Route::post('campaigns/stats/variant/{variant}/payment/step/{step}/count', 'StatsController@variantPaymentStatsCount');
    Route::post('campaigns/stats/variant/{variant}/payment/step/{step}/sum', 'StatsController@variantPaymentStatsSum');





    Route::resource('banners', 'BannerController');
    Route::resource('campaigns', 'CampaignController');
    Route::resource('schedule', 'ScheduleController');
    Route::resource('campaigns.schedule', 'ScheduleController');
});

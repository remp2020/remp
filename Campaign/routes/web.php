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
    Route::get('schedule/json', 'ScheduleController@json')->name('schedule.json');
    Route::post('schedule/{schedule}/start', 'ScheduleController@start')->name('schedule.start');
    Route::post('schedule/{schedule}/pause', 'ScheduleController@pause')->name('schedule.pause');
    Route::post('schedule/{schedule}/stop', 'ScheduleController@stop')->name('schedule.stop');

    Route::get('auth/logout', 'AuthController@logout')->name('auth.logout');

    Route::resource('banners', 'BannerController');
    Route::resource('campaigns', 'CampaignController');
    Route::resource('schedule', 'ScheduleController');
});

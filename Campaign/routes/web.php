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

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SnippetController;
use App\Http\Controllers\CampaignsComparisonController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SearchController;

Route::get('/error', [AuthController::class, 'error'])->name('sso.error');

Route::get('banners/preview/{uuid}', [BannerController::class, 'preview'])->name('banners.preview');
Route::get('campaigns/showtime', [CampaignController::class, 'showtime'])->name('campaigns.showtime');

Route::middleware('auth.jwt')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('banners/json', [BannerController::class, 'json'])->name('banners.json');
    Route::get('snippets/json', [SnippetController::class, 'json'])->name('snippets.json');
    Route::get('banners/{sourceBanner}/copy', [BannerController::class, 'copy'])->name('banners.copy');
    Route::get('campaigns/json', [CampaignController::class, 'json'])->name('campaigns.json');
    Route::get('campaigns/{sourceCampaign}/copy', [CampaignController::class, 'copy'])->name('campaigns.copy');
    Route::get('campaigns/{campaign}/schedule/json', [ScheduleController::class, 'json'])->name('campaign.schedule.json');
    Route::get('schedule/json', [ScheduleController::class, 'json'])->name('schedule.json');
    Route::post('schedule/{schedule}/start', [ScheduleController::class, 'start'])->name('schedule.start');
    Route::post('schedule/{schedule}/pause', [ScheduleController::class, 'pause'])->name('schedule.pause');
    Route::post('schedule/{schedule}/stop', [ScheduleController::class, 'stop'])->name('schedule.stop');

    Route::get('comparison', [CampaignsComparisonController::class, 'index'])->name('comparison.index');
    Route::get('comparison/json', [CampaignsComparisonController::class, 'json'])->name('comparison.json');
    Route::put('comparison/{campaign}', [CampaignsComparisonController::class, 'add'])->name('comparison.add');
    Route::post('comparison/addAll', [CampaignsComparisonController::class, 'addAll'])->name('comparison.addAll');
    Route::post('comparison/removeAll', [CampaignsComparisonController::class, 'removeAll'])->name('comparison.removeAll');
    Route::delete('comparison/{campaign}/', [CampaignsComparisonController::class, 'remove'])->name('comparison.remove');

    Route::post('campaigns/validate', [CampaignController::class, 'validateForm'])->name('campaigns.validateForm');
    Route::post('banners/validate', [BannerController::class, 'validateForm'])->name('banners.validateForm');
    Route::post('snippets/validate/{snippet?}', [SnippetController::class, 'validateForm'])->name('snippets.validateForm');

    Route::get('campaigns/{campaign}/stats', [CampaignController::class, 'stats'])->name('campaigns.stats');
    Route::post('campaigns/{campaign}/stats/data', [StatsController::class, 'getStats'])->name('campaigns.stats.data');

    Route::get('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::resource('banners', BannerController::class);
    Route::resource('campaigns', CampaignController::class);
    Route::resource('snippets', SnippetController::class);
    Route::resource('schedule', ScheduleController::class)->only(['index', 'create', 'edit', 'update', 'destroy']);
    Route::resource('campaigns.schedule', ScheduleController::class);

    Route::get('search', [SearchController::class, 'search'])->name('search');
});

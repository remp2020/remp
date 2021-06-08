<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleDetailsController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\JournalProxyController;
use App\Http\Controllers\TagController;

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
    Route::apiResource('articles', ArticleController::class, [
        'only' => ['index', 'store'],
    ]);
    Route::apiResource('conversions', ConversionController::class, [
        'only' => ['index', 'store']
    ]);
    Route::post('articles/unread', [ArticleController::class, 'unreadArticlesForUsers'])->name('articles.unreadArticlesForUsers');
    Route::post('articles/upsert', [ArticleController::class, 'upsert'])->name('articles.upsert');
    Route::post('conversions/upsert', [ConversionController::class, 'upsert'])->name('conversions.upsert');

    // TODO: this is temporary solution - will be refactored in https://gitlab.com/remp/remp/-/issues/601
    Route::post('v2/articles/upsert', [ArticleController::class, 'upsertV2'])->name('articles.upsertV2');

    Route::post('articles/top', [ArticleController::class, 'topArticles'])->name('articles.top');
    Route::post('authors/top', [AuthorController::class, 'topAuthors'])->name('authors.top');
    Route::post('tags/top', [TagController::class, 'topTags'])->name('tags.top');

    Route::get('article/{article?}', [ArticleDetailsController::class, 'show']);
    Route::get('article/{article}/histogram', [ArticleDetailsController::class, 'timeHistogram']);
    Route::get('article/{article}/variants-histogram', [ArticleDetailsController::class, 'variantsHistogram']);

    Route::get('/journal/concurrents/count/', [JournalController::class, 'concurrentsCount']);
    Route::match(['GET', 'POST'], '/journal/concurrents/count/articles', [JournalController::class, 'articlesConcurrentsCount']);
    Route::post('/journal/concurrents/count/', [JournalController::class, 'concurrentsCount']);

    // Pure proxy calls to Journal API (TODO: rework to more user-friendly API)
    Route::post('/journal/pageviews/actions/progress/count', [JournalProxyController::class, 'pageviewsProgressCount']);
    Route::post('/journal/pageviews/actions/load/unique/browsers', [JournalProxyController::class, 'pageviewsUniqueBrowsersCount']);
    Route::post('/journal/commerce/steps/purchase/count', [JournalProxyController::class, 'commercePurchaseCount']);
});

Route::get('/journal/{group}/categories/{category}/actions', [JournalController::class, 'actions']);
Route::get('/journal/flags', [JournalController::class, 'flags']);

Route::middleware('cors')->group(function() {
    Route::get('/dashboard/options', [DashboardController::class, 'options'])->name('dashboard.options');
});


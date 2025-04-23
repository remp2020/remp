<?php

use Illuminate\Http\Middleware\HandleCors;
use Remp\BeamModule\Http\Controllers\Api\v1\ArticleController as ArticleControllerApiV1;
use Remp\BeamModule\Http\Controllers\Api\v1\AuthorController as AuthorControllerApiV1;
use Remp\BeamModule\Http\Controllers\Api\v1\JournalController;
use Remp\BeamModule\Http\Controllers\Api\v1\PageviewController;
use Remp\BeamModule\Http\Controllers\Api\v1\TagController as TagControllerApiV1;
use Remp\BeamModule\Http\Controllers\Api\v2\ArticleController as ArticleControllerApiV2;
use Remp\BeamModule\Http\Controllers\Api\v2\AuthorController as AuthorControllerApiV2;
use Remp\BeamModule\Http\Controllers\Api\v2\TagController as TagControllerApiV2;
use Remp\BeamModule\Http\Controllers\ArticleController;
use Remp\BeamModule\Http\Controllers\ArticleDetailsController;
use Remp\BeamModule\Http\Controllers\ConversionController;
use Remp\BeamModule\Http\Controllers\DashboardController;
use Remp\BeamModule\Http\Controllers\JournalProxyController;

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

Route::middleware('auth:api')->group(function () {
    Route::apiResource('articles', ArticleController::class, [
        'only' => ['index', 'store'],
    ]);
    Route::apiResource('conversions', ConversionController::class, [
        'only' => ['index', 'store']
    ]);
    Route::post('articles/unread', [ArticleControllerApiV1::class, 'unreadArticlesForUsers'])->name('articles.unreadArticlesForUsers');
    Route::post('articles/read', [ArticleControllerApiV1::class, 'readArticles'])->name('articles.readArticles');
    Route::post('articles/upsert', [ArticleControllerApiV1::class, 'upsert'])->name('articles.upsert');
    Route::post('conversions/upsert', [ConversionController::class, 'upsert'])->name('conversions.upsert');

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

    Route::post('articles/top', [ArticleControllerApiV1::class, 'topArticles'])->name('articles.top');
    Route::post('authors/top', [AuthorControllerApiV1::class, 'topAuthors'])->name('authors.top');
    Route::post('tags/top', [TagControllerApiV1::class, 'topTags'])->name('tags.top');

    Route::post('pageviews/histogram', [PageviewController::class, 'timeHistogram']);

    Route::group(['prefix' => 'v2'], function () {
        Route::post('articles/top', [ArticleControllerApiV2::class, 'topArticles'])->name('articles.top.v2');
        Route::post('authors/top', [AuthorControllerApiV2::class, 'topAuthors'])->name('authors.top.v2');
        Route::post('tags/top', [TagControllerApiV2::class, 'topTags'])->name('tags.top.v2');
        Route::post('articles/upsert', [ArticleControllerApiV2::class, 'upsert'])->name('articles.upsert.v2');
    });

    Route::get('authors', [\Remp\BeamModule\Http\Controllers\AuthorController::class, 'index'])->name('authors.index');
    Route::get('sections', [\Remp\BeamModule\Http\Controllers\SectionController::class, 'index'])->name('sections.index');
    Route::get('tags', [\Remp\BeamModule\Http\Controllers\TagController::class, 'index'])->name('tags.index');
});

Route::get('/journal/{group}/categories/{category}/actions', [JournalController::class, 'actions']);
Route::get('/journal/flags', [JournalController::class, 'flags']);

Route::middleware(HandleCors::class)->group(function () {
    Route::get('/dashboard/options', [DashboardController::class, 'options'])->name('dashboard.options');
});

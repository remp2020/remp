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
    Route::apiResource('articles', 'ArticleController', [
        'only' => ['store'],
    ]);
    Route::apiResource('conversions', 'ConversionController', [
        'only' => ['index', 'store']
    ]);
    Route::post('articles/unread', 'ArticleController@unreadArticlesForUsers')->name('articles.unreadArticlesForUsers');
    Route::post('articles/upsert', 'ArticleController@upsert')->name('articles.upsert');
    Route::post('conversions/upsert', 'ConversionController@upsert')->name('conversions.upsert');

    // TODO: this is temporary solution - will be refactored in https://gitlab.com/remp/remp/-/issues/601
    Route::post('v2/articles/upsert', 'ArticleController@upsertV2')->name('articles.upsertV2');

    Route::post('articles/top', 'ArticleController@topArticles')->name('articles.top');
    Route::post('authors/top', 'AuthorController@topAuthors')->name('authors.top');
    Route::post('tags/top', 'TagController@topTags')->name('tags.top');

    Route::middleware('cors')->group(function() {
        Route::get('article/{article?}', 'ArticleDetailsController@show');
        Route::get('article/{article}/histogram', 'ArticleDetailsController@timeHistogram');
        Route::get('article/{article}/variants-histogram', 'ArticleDetailsController@variantsHistogram');

        Route::get('/journal/concurrents/count/', 'JournalController@concurrentsCount');
        Route::match(['GET', 'POST'], '/journal/concurrents/count/articles', 'JournalController@articlesConcurrentsCount');
        Route::post('/journal/concurrents/count/', 'JournalController@concurrentsCount');

        // Pure proxy calls to Journal API (TODO: rework to more user-friendly API)
        Route::post('/journal/pageviews/actions/progress/count', 'JournalProxyController@pageviewsProgressCount');
        Route::post('/journal/pageviews/actions/load/unique/browsers', 'JournalProxyController@pageviewsUniqueBrowsersCount');
        Route::post('/journal/commerce/steps/purchase/count', 'JournalProxyController@commercePurchaseCount');
    });
});

Route::get('/journal/{group}/categories/{category}/actions', 'JournalController@actions');
Route::get('/journal/flags', 'JournalController@flags');

Route::middleware('cors')->group(function() {
    Route::get('/dashboard/options', 'DashboardController@options')->name('dashboard.options');
});


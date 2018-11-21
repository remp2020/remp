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

// Temporarily use basic auth for public dashboard
// TODO: remove once authentication layer is done
Route::middleware('auth.basic.dashboard')->group(function () {
    Route::get('public', 'DashboardController@public')->name('dashboard.public');
    Route::post('public/articlesJson', 'DashboardController@mostReadArticles')->name('public.articles.json');
    Route::post('public/timeHistogramJson', 'DashboardController@timeHistogram')->name('public.timeHistogram.json');
});

Route::middleware('auth.jwt')->group(function () {
    Route::get('/', 'DashboardController@index')->name('dashboard.index');
    Route::get('dashboard', 'DashboardController@index')->name('dashboard.index');
    Route::post('dashboard/articlesJson', 'DashboardController@mostReadArticles')->name('dashboard.articles.json');
    Route::post('dashboard/timeHistogramJson', 'DashboardController@timeHistogram')->name('dashboard.timeHistogram.json');

    Route::get('accounts/json', 'AccountController@json');
    Route::get('accounts/{account}/properties/json', 'PropertyController@json')->name('accounts.properties.json');

    Route::get('segments/json', 'SegmentController@json')->name('segments.json');
    Route::get('segments/{sourceSegment}/copy', 'SegmentController@copy')->name('segments.copy');

    Route::get('articles/conversions', 'ArticleController@conversions')->name('articles.conversions');
    Route::get('articles/dtConversions', 'ArticleController@dtConversions')->name('articles.dtConversions');
    Route::get('articles/pageviews', 'ArticleController@pageviews')->name('articles.pageviews');
    Route::get('articles/dtPageviews', 'ArticleController@dtPageviews')->name('articles.dtPageviews');
    Route::post('articles/upsert', 'ArticleController@upsert')->name('articles.upsert');

    Route::get('conversions/json', 'ConversionController@json')->name('conversions.json');
    Route::post('conversions/upsert', 'ConversionController@upsert')->name('conversions.upsert');

    Route::get('authors/dtAuthors', 'AuthorController@dtAuthors')->name('authors.dtAuthors');
    Route::get('authors/{author}/dtArticles', 'AuthorController@dtArticles')->name('authors.dtArticles');

    Route::get('visitors/devices', 'VisitorController@devices')->name('visitors.devices');
    Route::get('visitors/sources', 'VisitorController@sources')->name('visitors.sources');
    Route::get('visitors/dtBrowsers', 'VisitorController@dtBrowsers')->name('visitors.dtBrowsers');
    Route::get('visitors/dtDevices', 'VisitorController@dtDevices')->name('visitors.dtDevices');
    Route::get('visitors/dtReferers', 'VisitorController@dtReferers')->name('visitors.dtReferers');

    Route::get('auth/logout', 'AuthController@logout')->name('auth.logout');

    Route::resource('accounts', 'AccountController');
    Route::resource('accounts.properties', 'PropertyController');

    Route::resource('segments', 'SegmentController');

    Route::get('articles/{article}/histogramJson', 'ArticleDetailsController@timeHistogram')->name('articles.timeHistogram.json');
    Route::resource('articles', 'ArticleController', [
        'only' => ['store'],
    ]);

    Route::resource('articles', 'ArticleDetailsController', [
        'only' => ['show'],
    ]);

    Route::get('newsletters/json', 'NewsletterController@json')->name('newsletters.json');
    Route::post('newsletters/validate', 'NewsletterController@validateForm')->name('newsletters.validateForm');
    Route::post('newsletters/{newsletter}/start', 'NewsletterController@start')->name('newsletters.start');
    Route::post('newsletters/{newsletter}/pause', 'NewsletterController@pause')->name('newsletters.pause');
    Route::resource('newsletters', 'NewsletterController', ['except' => ['show']]);

    Route::resource('conversions', 'ConversionController', [
        'only' => ['index', 'store', 'show']
    ]);
    Route::resource('authors', 'AuthorController', [
        'only' => ['index', 'show']
    ]);

    Route::post('entities/validate/{entity?}', 'EntitiesController@validateForm')->name('entities.validateForm');
    Route::get('entities/json', 'EntitiesController@json')->name('entities.json');
    Route::resource('entities', 'EntitiesController');

    // TODO: temporary, delete after test is over
    Route::get('tests/author-segments-test', 'TestController@authorSegmentsTest')->name('test.author-segments-test');
    Route::post('tests/author-segments-test', 'TestController@showResults')->name('test.show-results');
});

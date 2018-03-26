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

Route::middleware('auth.jwt')->group(function () {
    Route::get('/', 'DashboardController@index')->name('dashboard');
    Route::get('dashboard', 'DashboardController@index')->name('dashboard');

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

    Route::resource('articles', 'ArticleController', [
        'only' => ['store'],
    ]);
    Route::resource('conversions', 'ConversionController', [
        'only' => ['index', 'store']
    ]);
    Route::resource('authors', 'AuthorController', [
        'only' => ['index', 'show']
    ]);
});

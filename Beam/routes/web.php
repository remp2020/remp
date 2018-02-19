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

Route::get('/', function () {
    return redirect(route('dashboard'));
});

Route::get('/error', function() {
    return 'error during login: ' . $_GET['error'];
})->name('sso.error');

Route::middleware('auth.jwt')->group(function () {
    Route::get('accounts/json', 'AccountController@json');
    Route::get('accounts/{account}/properties/json', 'PropertyController@json')->name('accounts.properties.json');
    Route::get('segments/json', 'SegmentController@json')->name('segments.json');
    Route::get('segments/{sourceSegment}/copy', 'SegmentController@copy')->name('segments.copy');
    Route::get('dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('articles/json', 'ArticleController@json')->name('articles.json');
    Route::get('conversions/json', 'ConversionController@json')->name('conversions.json');

    Route::resource('accounts', 'AccountController');
    Route::resource('accounts.properties', 'PropertyController');
    Route::resource('segments', 'SegmentController');
    Route::resource('articles', 'ArticleController', [
        'only' => ['index', 'store'],
    ]);
    Route::resource('conversions', 'ConversionController', [
        'only' => ['store', 'index']
    ]);
});

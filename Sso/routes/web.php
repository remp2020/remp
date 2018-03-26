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

Route::get('/', 'ApiTokenController@index');

Route::get('/error', 'AuthController@error')->name('sso.error');

Route::middleware('auth.jwt')->group(function () {
    Route::get('api-tokens/json', 'ApiTokenController@json')->name('api-tokens.json');
    Route::resource('api-tokens', 'ApiTokenController');
});

Route::get('auth/login', 'AuthController@login')->name('auth.login');
Route::get('auth/logout', 'AuthController@logout')->name('auth.logout');
Route::get('auth/logout-web', 'AuthController@logoutWeb')->name('auth.logout-web');
Route::get('auth/google', 'Auth\GoogleController@redirect')->name('auth.google');
Route::get('auth/google/callback', 'Auth\GoogleController@callback');

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

Route::middleware('app.jwt.auth')->group(function() {
    Route::get('auth/introspect', 'AuthController@introspect')->name('auth.introspect');
});

Route::post('auth/refresh', 'AuthController@refresh')->name('auth.refresh');
Route::post('auth/invalidate', 'AuthController@invalidate')->name('auth.invalidate');
Route::get('auth/api-token', 'AuthController@apiToken')->name('auth.api-token');

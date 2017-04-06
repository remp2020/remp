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

Route::get('accounts/json', 'AccountController@json');
Route::get('accounts/{account}/properties/json', 'PropertyController@json')->name('accounts.properties.json');
Route::get('dashboard', 'DashboardController@index')->name('dashboard');

Route::resource('accounts', 'AccountController');
Route::resource('accounts.properties', 'PropertyController');

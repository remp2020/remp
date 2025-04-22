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

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Remp\LaravelSso\Http\Middleware\VerifyJwtToken;

Route::get('/error', [AuthController::class, 'error'])->name('sso.error');

Route::middleware(VerifyJwtToken::class)->group(function () {
    Route::get('/', [ApiTokenController::class, 'index']);
    Route::get('api-tokens/json', [ApiTokenController::class, 'json'])->name('api-tokens.json');
    Route::resource('api-tokens', ApiTokenController::class);
    Route::get('auth/logout-web', [AuthController::class, 'logoutWeb'])->name('auth.logout-web');
    Route::get('settings/jwtwhitelist', [SettingsController::class, 'jwtwhitelist'])->name('settings.jwtwhitelist');
});

Route::get('auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::get('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'callback']);

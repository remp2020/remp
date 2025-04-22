<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\VerifyUserToken;
use Illuminate\Support\Facades\Route;

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

Route::middleware(VerifyUserToken::class)->group(function() {
    Route::get('auth/introspect', [AuthController::class, 'introspect'])->name('auth.introspect');
});


Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
Route::post('auth/invalidate', [AuthController::class, 'invalidate'])->name('auth.invalidate');
Route::get('auth/check-token', [AuthController::class, 'apiToken'])->name('auth.check-token');

<?php

use App\Http\Controllers\Api\SegmentBrowsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::middleware('auth:api')->group(function () {
    Route::put('segments/{code}/browsers', [SegmentBrowsersController::class, 'replace'])
        ->where('code', '[a-z0-9][a-z0-9_-]*')
        ->name('api.segments.browsers.replace');
});

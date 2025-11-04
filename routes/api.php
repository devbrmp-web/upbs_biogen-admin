<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommodityController;
use App\Http\Controllers\Api\VarietyController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API for client (guest) with rate limiting
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/commodities', [CommodityController::class, 'index']);
    Route::get('/varieties', [VarietyController::class, 'index']);
    Route::get('/varieties/{slug}', [VarietyController::class, 'show']);
});

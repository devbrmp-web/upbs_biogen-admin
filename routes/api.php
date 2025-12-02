<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommodityController;
use App\Http\Controllers\Api\VarietyController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SeedClassController;
use App\Http\Controllers\Api\SeedLotController;

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

// Public API for client (guest) dengan rate limiting
Route::middleware('throttle:20,1')->group(function () {
    Route::get('/commodities', [CommodityController::class, 'index']);
    Route::get('/varieties', [VarietyController::class, 'index']);
    Route::get('/varieties/{slug}', [VarietyController::class, 'show']);
    Route::get('/seed-classes', [SeedClassController::class, 'index'])->name('api.seed-classes.index');
    Route::get('/seed-classes/{code}', [SeedClassController::class, 'show'])->name('api.seed-classes.show');
    Route::get('/seed-lots', [SeedLotController::class, 'index'])->name('api.seed-lots.index');
    Route::get('/seed-lots/{lot_code}', [SeedLotController::class, 'show'])->name('api.seed-lots.show');
    Route::post('/orders/checkout', [OrderController::class, 'store'])
        ->name('api.orders.checkout')
        ->middleware('throttle:20,1');
    Route::get('/orders/track/{tracking_number}', [OrderController::class, 'track'])
        ->name('api.orders.track')
        ->middleware('throttle:20,1');
    Route::get('/orders/{order_code}/payment/status', [OrderController::class, 'verifyPaymentStatus'])
        ->name('api.orders.payment.status')
        ->middleware('throttle:20,1');
});

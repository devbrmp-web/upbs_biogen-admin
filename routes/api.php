<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CommodityController;
use App\Http\Controllers\Api\VarietyController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SeedClassController;
use App\Http\Controllers\Api\SeedLotController;
use App\Http\Controllers\Api\VarietyImageController;
use App\Http\Controllers\WebhookController;

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

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/commodities', [CommodityController::class, 'index']);
    Route::get('/varieties', [VarietyController::class, 'index']);
    Route::get('/varieties/{slug}', [VarietyController::class, 'show']);
    Route::get('/seed-classes/{id}/varieties', [VarietyController::class, 'bySeedClass'])
        ->whereNumber('id');
    Route::get('/seed-classes', [SeedClassController::class, 'index'])->name('api.seed-classes.index');
    Route::get('/seed-classes/{code}', [SeedClassController::class, 'show'])->name('api.seed-classes.show');
    Route::get('/seed-lots', [SeedLotController::class, 'index'])->name('api.seed-lots.index');
    Route::get('/seed-lots/{lot_code}', [SeedLotController::class, 'show'])->name('api.seed-lots.show');
    Route::post('/orders/checkout', [OrderController::class, 'store'])
        ->name('api.orders.checkout');
    Route::get('/orders/track/{tracking_number}', [OrderController::class, 'track'])
        ->name('api.orders.track');
    Route::get('/orders/track', [OrderController::class, 'track'])
        ->name('api.orders.track.query');
    Route::get('/orders/{order_code}', [OrderController::class, 'getPublicOrder'])
        ->name('api.orders.show');
    Route::get('/orders/{order_code}/payment/status', [OrderController::class, 'verifyPaymentStatus'])
        ->name('api.orders.payment.status');
    Route::get('/orders/{order_code}/payment/snap-token', [OrderController::class, 'getSnapToken'])
        ->name('api.orders.payment.snap-token');
    Route::post('/orders/payment/sync', [OrderController::class, 'syncPaymentByOrderId'])
        ->name('api.orders.payment.sync');
    Route::post('/webhooks/midtrans', [WebhookController::class, 'handleMidtransNotification'])
        ->name('webhooks.midtrans');
    
    // Manual Transfer Payment Routes
    Route::post('/orders/{code}/confirm-payment', [OrderController::class, 'confirmPayment'])
        ->name('api.orders.confirm-payment');
    Route::get('/orders/{code}/payment-info', [OrderController::class, 'getPaymentInfo'])
        ->name('api.orders.payment-info');
});


// Fallback for API routes
Route::fallback(function(){
    return response()->json(['message' => 'API Endpoint Not Found.'], 404);
});

Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class, 'throttle:10,1'])->group(function () {
    Route::post('/varieties/{variety}/images', [VarietyImageController::class, 'store'])
        ->whereNumber('variety');
    Route::put('/varieties/{variety}/images/{image}/primary', [VarietyImageController::class, 'setPrimary'])
        ->whereNumber('variety')
        ->whereNumber('image');
    Route::delete('/varieties/{variety}/images/{image}', [VarietyImageController::class, 'destroy'])
        ->whereNumber('variety')
        ->whereNumber('image');
});

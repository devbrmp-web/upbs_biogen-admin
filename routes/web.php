<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// Auth routes
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

// Admin area
Route::middleware(['auth', \App\Http\Middleware\EnsureAdmin::class])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export', [\App\Http\Controllers\Admin\DashboardController::class, 'export'])->name('dashboard.export');
        Route::get('/dashboard/stats', [\App\Http\Controllers\Admin\DashboardController::class, 'getStats'])->name('dashboard.stats');
        Route::get('/dashboard/charts', [\App\Http\Controllers\Admin\DashboardController::class, 'getCharts'])->name('dashboard.charts');
        Route::get('/dashboard/stock', [\App\Http\Controllers\Admin\DashboardController::class, 'getStock'])->name('dashboard.stock');
        Route::get('/dashboard/top-products', [\App\Http\Controllers\Admin\DashboardController::class, 'getTopProducts'])->name('dashboard.top-products');
        Route::get('/dashboard/heatmap', [\App\Http\Controllers\Admin\DashboardController::class, 'getHeatmap'])->name('dashboard.heatmap');

        // Commodities (formerly Categories)
        Route::resource('commodities', \App\Http\Controllers\Admin\CommodityController::class);

        // Varieties (formerly Products)
        Route::resource('varieties', \App\Http\Controllers\Admin\VarietyController::class);
        Route::post('varieties/temp-image', [\App\Http\Controllers\Admin\VarietyController::class, 'tempImageUpload'])->name('varieties.temp-image');
        Route::post('varieties/{variety}/images', [\App\Http\Controllers\Admin\VarietyController::class, 'storeImages'])->name('varieties.images.store');
        Route::delete('varieties/{variety}/images/{image}', [\App\Http\Controllers\Admin\VarietyController::class, 'destroyImage'])->name('varieties.images.destroy');
        Route::post('varieties/{variety}/images/reorder', [\App\Http\Controllers\Admin\VarietyController::class, 'reorderImages'])->name('varieties.images.reorder');
        Route::post('varieties/{variety}/images/{image}/primary', [\App\Http\Controllers\Admin\VarietyController::class, 'setPrimaryImage'])->name('varieties.images.primary');

        // Seed Classes
        Route::resource('seed-classes', \App\Http\Controllers\Admin\SeedClassController::class);

        // Seed Lots
        Route::resource('seed-lots', \App\Http\Controllers\Admin\SeedLotController::class);

        // Orders
        Route::resource('orders', \App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show', 'destroy']);
        Route::patch('orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::patch('orders/{order}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/bulk-cancel', [\App\Http\Controllers\Admin\OrderController::class, 'bulkCancel'])->name('orders.bulk-cancel');
        Route::post('orders/bulk-update-status', [\App\Http\Controllers\Admin\OrderController::class, 'bulkUpdateStatus'])->name('orders.bulk-update-status');
        Route::post('orders/export', [\App\Http\Controllers\Admin\OrderController::class, 'export'])->name('orders.export');

        // Payment manual sync (Midtrans GET Status)
        Route::get('orders/{order}/payments/sync-midtrans', [\App\Http\Controllers\Admin\PaymentSyncController::class, 'syncMidtransStatus'])
            ->name('orders.payments.sync-midtrans');

        // Audit Logs
        Route::resource('audit-logs', \App\Http\Controllers\Admin\AuditLogController::class)->only(['index', 'show']);

        // Profile & Help
        Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('profile');
        Route::get('help', [\App\Http\Controllers\Admin\HelpController::class, 'index'])->name('help');
    });

// Super Admin only routes
Route::middleware(['auth', \App\Http\Middleware\EnsureSuperAdmin::class])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        // Admin User Management (Super Admin only)
        Route::resource('admin-users', \App\Http\Controllers\Admin\AdminUserController::class)
            ->parameters(['admin-users' => 'adminUser']);
    });

// Root publik sementara
Route::get('/', fn() => redirect('/login'));

// Client routes (public)
Route::prefix('client')->name('client.')->group(function () {
    // Checkout
    Route::get('/checkout', [\App\Http\Controllers\Client\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/process', [\App\Http\Controllers\Client\CheckoutController::class, 'process'])->name('checkout.process');
    Route::view('/catalog', 'client.seed.index')->name('catalog');
    Route::view('/cart', 'client.cart.index')->name('cart');

    // Order confirmation (simple placeholder view/response)
    Route::get('/orders/confirmation/{order_code}', function (string $order_code) {
        return response()->view('client.orders.confirmation', ['order_code' => $order_code], 200)->header('Content-Type', 'text/html');
    })->name('order.confirmation');

    // Order tracking route used in email templates
    Route::get('/orders/track', function () {
        return response('Order Tracking Placeholder', 200);
    })->name('orders.track');
});

// Webhook routes (no CSRF protection needed)
Route::post('/webhook/payment', [\App\Http\Controllers\WebhookController::class, 'handlePayment'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.payment');

// Alias untuk client yang memanggil tanpa prefix /api
Route::post('/orders/payment/sync', [\App\Http\Controllers\Api\OrderController::class, 'syncPaymentByOrderId'])
    ->name('orders.payment.sync')
    ->middleware('throttle:20,1')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/orders/track/{tracking_number}', [\App\Http\Controllers\Api\OrderController::class, 'track'])
    ->name('orders.track.alias')
    ->middleware('throttle:20,1');

Route::get('/orders/track', [\App\Http\Controllers\Api\OrderController::class, 'track'])
    ->name('orders.track.query.alias')
    ->middleware('throttle:20,1');

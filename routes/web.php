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
        Route::view('/dashboard', 'dashboards.analytics')->name('dashboard');

        // Commodities (formerly Categories)
        Route::resource('commodities', \App\Http\Controllers\Admin\CommodityController::class);

        // Varieties (formerly Products)
        Route::resource('varieties', \App\Http\Controllers\Admin\VarietyController::class);

        // Seed Classes
        Route::resource('seed-classes', \App\Http\Controllers\Admin\SeedClassController::class);

        // Seed Lots
        Route::resource('seed-lots', \App\Http\Controllers\Admin\SeedLotController::class);
    });

// Root publik sementara
Route::get('/', fn() => redirect('/login'));


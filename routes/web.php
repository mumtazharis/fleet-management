<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\DriverController;
use App\Http\Controllers\Master\LocationController;
use App\Http\Controllers\Master\RentalCompanyController;
use App\Http\Controllers\Master\VehicleController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard (if auth) or login (if guest)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected Application Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Master Data Routes
    Route::resource('vehicles', VehicleController::class);
    Route::resource('drivers', DriverController::class);
    Route::resource('rental-companies', RentalCompanyController::class);
    Route::resource('locations', LocationController::class);
});

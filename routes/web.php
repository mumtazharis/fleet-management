<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\DriverController;
use App\Http\Controllers\Master\LocationController;
use App\Http\Controllers\Master\RentalCompanyController;
use App\Http\Controllers\Master\VehicleController;
use App\Http\Controllers\Monitoring\FuelLogController;
use App\Http\Controllers\Monitoring\ServiceLogController;
use App\Http\Controllers\Transaction\BookingApprovalController;
use App\Http\Controllers\Transaction\VehicleBookingController;
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
    Route::get('/vehicles-options', [VehicleController::class, 'options'])->name('vehicles.options');
    Route::resource('vehicles', VehicleController::class);
    Route::resource('drivers', DriverController::class);
    Route::resource('rental-companies', RentalCompanyController::class);
    Route::get('/locations-options', [LocationController::class, 'options'])->name('locations.options');
    Route::resource('locations', LocationController::class);

    // Transaction Routes (Pemesanan Kendaraan & Persetujuan Berjenjang)
    Route::get('/bookings/export', [VehicleBookingController::class, 'export'])->name('bookings.export');
    Route::get('/bookings-options', [VehicleBookingController::class, 'options'])->name('bookings.options');
    Route::post('/bookings/{booking}/complete', [VehicleBookingController::class, 'complete'])->name('bookings.complete');
    Route::resource('bookings', VehicleBookingController::class);

    Route::get('/approvals', [BookingApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{approval}/approve', [BookingApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{approval}/reject', [BookingApprovalController::class, 'reject'])->name('approvals.reject');

    // Monitoring Routes
    Route::get('/fuel-logs-options', [FuelLogController::class, 'options'])->name('fuel-logs.options');
    Route::resource('fuel-logs', FuelLogController::class);
    Route::get('/service-logs-options', [ServiceLogController::class, 'options'])->name('service-logs.options');
    Route::post('/service-logs/{service_log}/complete', [ServiceLogController::class, 'complete'])->name('service-logs.complete');
    Route::post('/service-logs/{service_log}/cancel', [ServiceLogController::class, 'cancel'])->name('service-logs.cancel');
    Route::resource('service-logs', ServiceLogController::class);

    Route::resource('activity-logs', \App\Http\Controllers\Monitoring\ActivityLogController::class)->only(['index', 'show']);
});

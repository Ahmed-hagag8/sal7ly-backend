<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\Shared\ProfileController;

// ==========================================
// PUBLIC ROUTES (no authentication needed)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/customer', [RegisterController::class, 'customer']);
Route::post('/register/technician', [RegisterController::class, 'technician']);

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Sal7ly API is running!',
        'timestamp' => now()->toISOString(),
    ]);
});

// ==========================================
// PROTECTED ROUTES (authentication required)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/image', [ProfileController::class, 'uploadImage']);
});

// Admin only routes (Day 22-24)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Admin dashboard routes here
});
// Customer only routes
Route::middleware(['auth:sanctum', 'role:customer'])->prefix('customer')->group(function () {
    // Customer specific routes
});
// Technician routes (must be verified)
Route::middleware(['auth:sanctum', 'role:technician', 'verified.technician'])->prefix('technician')->group(function () {
    // Technician routes requiring verification
});
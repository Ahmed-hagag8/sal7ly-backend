<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\Auth\RegisterController;

// ==========================================
// PUBLIC ROUTES (no authentication needed)
// ==========================================

Route::post('/login', [AuthController::class, 'login']);

// Registration routes
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
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\Shared\ProfileController;
use App\Http\Controllers\v1\Shared\ServiceCategoryController;
use App\Http\Controllers\v1\Technician\DocumentController;
use App\Http\Controllers\v1\Admin\TechnicianController as AdminTechnicianController;


// PUBLIC ROUTES (no authentication needed)

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


// PROTECTED ROUTES (authentication required)

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/image', [ProfileController::class, 'uploadImage']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Technician verification
    Route::get('/technicians', [AdminTechnicianController::class, 'index']);
    Route::get('/technicians/{id}', [AdminTechnicianController::class, 'show']);
    Route::post('/technicians/{id}/approve', [AdminTechnicianController::class, 'approve']);
    Route::post('/technicians/{id}/reject', [AdminTechnicianController::class, 'reject']);
    
    // Document verification
    Route::post('/documents/{id}/approve', [AdminTechnicianController::class, 'approveDocument']);
    Route::post('/documents/{id}/reject', [AdminTechnicianController::class, 'rejectDocument']);
});
// Customer only routes
Route::middleware(['auth:sanctum', 'role:customer'])->prefix('customer')->group(function () {
    // Customer specific routes
});
// Technician routes (must be verified)
Route::middleware(['auth:sanctum', 'role:technician', 'verified.technician'])->prefix('technician')->group(function () {
    // Technician routes requiring verification
});

// PUBLIC ROUTES section:
Route::get('/categories', [ServiceCategoryController::class, 'index']);
Route::get('/categories/{id}', [ServiceCategoryController::class, 'show']);
Route::get('/categories/{id}/services', [ServiceCategoryController::class, 'services']);
Route::get('/services', [ServiceCategoryController::class, 'allServices']);

// Technician routes (authenticated technicians)
Route::middleware(['auth:sanctum', 'role:technician'])->prefix('technician')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
});
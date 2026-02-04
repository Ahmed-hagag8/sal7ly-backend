<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\AuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are prefixed with /api automatically
| Example: Route::get('/test') becomes GET /api/test
|
*/
// ==========================================
// PUBLIC ROUTES (no authentication needed)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);

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
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\Shared\ProfileController;
use App\Http\Controllers\v1\Shared\ServiceCategoryController;
use App\Http\Controllers\v1\Technician\DocumentController;
use App\Http\Controllers\v1\Admin\TechnicianController as AdminTechnicianController;
use App\Http\Controllers\v1\Customer\ServiceRequestController;
use App\Http\Controllers\v1\Technician\ServiceRequestController as TechnicianRequestController;
use App\Http\Controllers\v1\Technician\OfferController;
use App\Http\Controllers\v1\Shared\WalletController;
use App\Http\Controllers\v1\Customer\PaymentController;
use App\Http\Controllers\v1\Technician\WithdrawalController;
use App\Http\Controllers\v1\Technician\JobController;
use App\Http\Controllers\v1\Customer\ReviewController;
use App\Http\Controllers\v1\Technician\ReviewController as TechnicianReviewController;
use App\Http\Controllers\v1\Shared\ChatController;
use App\Http\Controllers\v1\Shared\NotificationController;
use App\Http\Controllers\v1\Admin\DashboardController;
use App\Http\Controllers\v1\Admin\CatalogController;
use App\Http\Controllers\v1\AIController;
use App\Http\Controllers\v1\Technician\LocationController;


// PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register/customer', [RegisterController::class, 'customer']);
Route::post('/register/technician', [RegisterController::class, 'technician']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/forgot-password-email', [AuthController::class, 'forgotPasswordEmail']);
Route::post('/reset-password-email', [AuthController::class, 'resetPasswordEmail']);
Route::get('/categories', [ServiceCategoryController::class, 'index']);
Route::get('/categories/{id}', [ServiceCategoryController::class, 'show']);
Route::get('/categories/{id}/services', [ServiceCategoryController::class, 'services']);
Route::get('/services', [ServiceCategoryController::class, 'allServices']);
Route::get('/cities', [CatalogController::class, 'cities']);
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Sal7ly API is running!',
        'timestamp' => now()->toISOString(),
    ]);
});


// PROTECTED ROUTES (authentication required)
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/send-email-otp', [AuthController::class, 'sendEmailOtp']);
    Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/image', [ProfileController::class, 'uploadImage']);
    Route::post('/profile/credentials', [ProfileController::class, 'changeCredentials']);
    Route::delete('/account', [ProfileController::class, 'deleteAccount']);
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::get('/conversations/{id}/messages', [ChatController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'send']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/ai/predict-price', [AIController::class, 'predictPrice']);
    Route::post('/ai/detect-image', [AIController::class, 'detectImage']);
    Route::post('/ai/chat', [AIController::class, 'chat']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'active', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/technicians', [AdminTechnicianController::class, 'index']);
    Route::get('/technicians/{id}', [AdminTechnicianController::class, 'show']);
    Route::post('/technicians/{id}/approve', [AdminTechnicianController::class, 'approve']);
    Route::post('/technicians/{id}/reject', [AdminTechnicianController::class, 'reject']);
    Route::post('/documents/{id}/approve', [AdminTechnicianController::class, 'approveDocument']);
    Route::post('/documents/{id}/reject', [AdminTechnicianController::class, 'rejectDocument']);
    Route::post('/withdrawals/{id}/process', [AdminTechnicianController::class, 'processWithdrawal']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity']);
    Route::get('/users', [DashboardController::class, 'users']);
    Route::get('/users/{id}', [DashboardController::class, 'showUser']);
    Route::post('/users/{id}/toggle-active', [DashboardController::class, 'toggleUserActive']);
    Route::post('/users/{id}/block', [DashboardController::class, 'blockUser']);
    Route::get('/requests', [DashboardController::class, 'requests']);
    Route::get('/requests/{id}', [DashboardController::class, 'showRequest']);
    Route::get('/billing/transactions', [DashboardController::class, 'transactions']);
    Route::get('/billing/withdrawals', [DashboardController::class, 'withdrawals']);
    Route::get('/billing/wallet-overview', [DashboardController::class, 'walletOverview']);
    Route::get('/billing/wallets', [DashboardController::class, 'wallets']);
    Route::get('/reports/requests', [DashboardController::class, 'requestStats']);
    Route::get('/reports/services', [DashboardController::class, 'serviceDistribution']);
    Route::get('/reports/revenue', [DashboardController::class, 'revenueByService']);
    Route::get('/reports/top-technicians', [DashboardController::class, 'topTechnicians']);
    Route::get('/reports/satisfaction', [DashboardController::class, 'customerSatisfaction']);
    Route::get('/reports/requests-breakdown', [DashboardController::class, 'requestsBreakdown']);
    Route::get('/reports/service-utilization', [DashboardController::class, 'serviceUtilization']);

    // Catalog management
    Route::post('/categories', [CatalogController::class, 'storeCategory']);
    Route::put('/categories/{id}', [CatalogController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [CatalogController::class, 'deleteCategory']);
    Route::post('/services', [CatalogController::class, 'storeService']);
    Route::put('/services/{id}', [CatalogController::class, 'updateService']);
    Route::delete('/services/{id}', [CatalogController::class, 'deleteService']);
    Route::get('/cities', [CatalogController::class, 'cities']);
    Route::post('/cities', [CatalogController::class, 'storeCity']);
    Route::put('/cities/{id}', [CatalogController::class, 'updateCity']);
    Route::delete('/cities/{id}', [CatalogController::class, 'deleteCity']);
});



// Technician document routes (allowed BEFORE verification so techs can upload docs for review)
Route::middleware(['auth:sanctum', 'role:technician', 'active'])->prefix('technician')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
});

// Technician routes (requires verified/approved technician)
Route::middleware(['auth:sanctum', 'role:technician', 'verified.technician', 'active'])->prefix('technician')->group(function () {
    Route::get('/requests', [TechnicianRequestController::class, 'index']);
    Route::get('/requests/{id}', [TechnicianRequestController::class, 'show']);
    Route::get('/offers', [OfferController::class, 'index']);
    Route::post('/requests/{id}/offer', [OfferController::class, 'store']);
    Route::delete('/offers/{id}', [OfferController::class, 'destroy']);
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::post('/jobs/{id}/start', [JobController::class, 'start']);
    Route::post('/jobs/{id}/complete', [JobController::class, 'complete']);
    Route::post('/location', [LocationController::class, 'update']);
    Route::post('/jobs/{id}/review', [TechnicianReviewController::class, 'store']);
});

// Customer routes
Route::middleware(['auth:sanctum', 'active', 'role:customer'])->prefix('customer')->group(function () {
    Route::post('/requests', [ServiceRequestController::class, 'store']);
    Route::get('/requests/{id}', [ServiceRequestController::class, 'show']);
    Route::post('/requests/{id}/cancel', [ServiceRequestController::class, 'cancel']);
    Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::get('/requests/{id}/offers', [ServiceRequestController::class, 'offers']);
    Route::post('/requests/{requestId}/offers/{offerId}/accept', [ServiceRequestController::class, 'acceptOffer']);
    Route::post('/jobs/{id}/pay', [PaymentController::class, 'pay']);
    Route::get('/jobs', [ServiceRequestController::class, 'jobs']);
    Route::get('/jobs/{id}', [ServiceRequestController::class, 'showJob']);
    Route::post('/jobs/{id}/review', [ReviewController::class, 'store']);
    Route::get('/jobs/{id}/technician-location', [LocationController::class, 'show']);
});
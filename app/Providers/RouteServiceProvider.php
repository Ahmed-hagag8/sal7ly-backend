<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * SEC-04: Added specific limiters for auth endpoints to prevent
     * brute-force attacks, OTP flooding, and mass account creation.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login: 5 attempts per minute per IP (brute-force protection)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again later.',
                    ], 429);
                });
        });

        // Registration: 3 per minute per IP (mass account creation protection)
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many registration attempts. Please try again later.',
                    ], 429);
                });
        });

        // OTP: 3 per minute per IP (SMS/email flooding protection)
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many OTP requests. Please try again later.',
                    ], 429);
                });
        });

        // Forgot password: 3 per minute per IP
        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many password reset requests. Please try again later.',
                    ], 429);
                });
        });
    }
}
